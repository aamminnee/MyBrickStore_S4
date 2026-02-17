<?php
namespace App\Models;

use App\Core\Model;
use App\Core\Db;
use PDO;
use Exception;

/**
 * Class MosaicModel
 * 
 ** Handles the core business logic for mosaics
 ** Manages temporary generation, persistence, cost calculation, and visualization of lego patterns
 *
 * @package App\Models
 */
class MosaicModel extends Model {

    /** @var string The database table associated with the model. */
    protected $table = 'Mosaic';

    /** @var int Coefficient applied to the raw cost to determine the selling price. */
    public const MARGIN_COEFF = 2;

    /** @var float Fixed fee for order preparation and handling. */
    public const HANDLING_FEE = 5.99;

    /** @var float Standard shipping cost. */
    public const DELIVERY_FEE = 4.99;

    /**
     * Generates multiple mosaic variations (e.g., standard, optimized, stock-based)
     * using the external java engine
     *
     * @param int $idImage the image identifier
     * @param string $blobData binary image content
     * @param string $extension image file extension
     * @return array resulting mosaic data (previews, costs, piece counts)
     * @throws Exception on file permission or locking issues
     */
    public function generateTemporaryMosaics($idImage, $blobData, $extension) {
        $projectRoot = dirname(__DIR__, 2); 
        $workDir = $projectRoot . '/JAVA/legotools';
        $jarPath = $projectRoot . '/bin/legotools-1.0-SNAPSHOT.jar';
        $inputDir = $projectRoot . '/JAVA/legotools/C/input';
        $outputDir = $projectRoot . '/JAVA/legotools/C/output';

        if (!is_writable($inputDir) || !is_writable($outputDir)) {
            throw new Exception("Erreur de permissions sur les dossiers input/output.");
        }

        $lockFile = $inputDir . '/generation.lock';
        $lockHandle = fopen($lockFile, 'w+');
        $results = [];

        if (flock($lockHandle, LOCK_EX)) {
            try {
                $this->updateBriquesFile($inputDir . '/briques.txt');

                $inputFilename = 'image_' . $idImage . '.' . $extension;
                $outputFilename = 'image_' . $idImage . '.' . $extension;
                $inputPath = $inputDir . '/' . $inputFilename;
                $outputPath = $outputDir . '/' . $outputFilename;

                file_put_contents($inputPath, $blobData);
                $execName = $projectRoot . '/bin/pavage'; 

                $cmd = sprintf(
                    'cd %s && java -jar %s pave %s %s %s all 2>&1',
                    escapeshellarg($workDir),
                    escapeshellarg($jarPath),
                    escapeshellarg($inputPath),
                    escapeshellarg($outputPath),
                    escapeshellarg($execName)
                );

                $execOutput = [];
                $returnCode = 0;
                exec($cmd, $execOutput, $returnCode);

                $searchPattern = $outputDir . '/image_' . $idImage . '*';
                $generatedFiles = glob($searchPattern);

                if ($generatedFiles) {
                    foreach ($generatedFiles as $file) {
                        $filename = basename($file);
                        $type = 'default';
                        
                        if (strpos($filename, 'minimisation') !== false) $type = 'minimisation';
                        elseif (strpos($filename, 'rentabilite') !== false || strpos($filename, 'rentable') !== false) $type = 'rentabilite';
                        elseif (strpos($filename, 'stock') !== false) $type = 'stock';
                        elseif (strpos($filename, 'libre') !== false) $type = 'libre';
                        else $type = 'libre';

                        if (!isset($results[$type])) {
                            $results[$type] = ['img' => null, 'txt' => null, 'count' => 0];
                        }

                        $info = pathinfo($file);

                        if (strpos($filename, 'inventory') !== false) {
                            $content = file_get_contents($file);
                            if (preg_match('/Total de briques\s*:\s*(\d+)/', $content, $matches)) {
                                $results[$type]['count'] = (int)$matches[1];
                            }
                            @unlink($file);
                        }
                        elseif (isset($info['extension']) && $info['extension'] === 'txt') {
                            $results[$type]['txt'] = file_get_contents($file);
                            @unlink($file);
                        }
                        elseif (isset($info['extension']) && in_array($info['extension'], ['png', 'jpg', 'jpeg'])) {
                            $imgContent = file_get_contents($file);
                            if ($imgContent) {
                                $mime = mime_content_type($file);
                                $results[$type]['img'] = "data:$mime;base64," . base64_encode($imgContent);
                            }
                            @unlink($file);
                        }
                    }
                }
                @unlink($inputPath);

            } finally {
                flock($lockHandle, LOCK_UN);
                fclose($lockHandle);
            }
        } else {
            throw new Exception("Impossible d'acquérir le verrou de génération.");
        }

        return $results;
    }

    /**
     * Persists a selected mosaic configuration to the database
     *
     * @param int $idImage
     * @param string $content layout instructions
     * @param string $type generation strategy used
     * @return int|false the new mosaic id or false on failure
     */
    public function saveSelectedMosaic($idImage, $content, $type) {
        $db = Db::getInstance();
        $sql = "INSERT INTO Mosaic (pavage, id_Image, generation_date) VALUES (?, ?, NOW())";
        $stmt = $db->prepare($sql);
        $stmt->bindParam(1, $content, PDO::PARAM_LOB);
        $stmt->bindParam(2, $idImage, PDO::PARAM_INT);
        
        if ($stmt->execute()) {
            return $db->lastInsertId();
        }
        return false;
    }

    /**
     * Generates a visual representation (png) of a stored mosaic
     *
     * @param int $idMosaic
     * @return string|null base64 encoded image or null
     */
    public function getMosaicVisual($idMosaic) {
        $db = Db::getInstance();
        $stmt = $db->prepare("SELECT pavage FROM Mosaic WHERE id_Mosaic = ?");
        $stmt->execute([$idMosaic]);
        $res = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$res || empty($res['pavage'])) {
            return null;
        }

        $pavageContent = $res['pavage'];
        $projectRoot = dirname(__DIR__, 2);
        $workDir = $projectRoot . '/JAVA/legotools';
        $inputDir = $workDir . '/C/input';
        $outputDir = $workDir . '/C/output';
        
        if (!is_dir($inputDir)) mkdir($inputDir, 0777, true);
        if (!is_dir($outputDir)) mkdir($outputDir, 0777, true);

        $uniqueId = uniqid();
        $txtFilename = 'visual_' . $uniqueId . '.txt';
        $pngFilename = 'visual_' . $uniqueId . '.png';
        
        $inputPath = $inputDir . '/' . $txtFilename;
        $outputPath = $outputDir . '/' . $pngFilename;

        file_put_contents($inputPath, $pavageContent);

        $jarPath = $projectRoot . '/bin/legotools-1.0-SNAPSHOT.jar';
        
        $cmd = sprintf(
            'cd %s && java -jar %s visualize %s %s 2>&1',
            escapeshellarg($workDir),
            escapeshellarg($jarPath),
            escapeshellarg($inputPath),
            escapeshellarg($outputPath)
        );

        $output = [];
        $returnCode = 0;
        exec($cmd, $output, $returnCode);

        $base64Image = null;

        if (file_exists($outputPath)) {
            $data = file_get_contents($outputPath);
            if ($data !== false) {
                $base64Image = 'data:image/png;base64,' . base64_encode($data);
            }
            @unlink($outputPath);
        } else {
            error_log("Erreur Java Visualize: " . implode(" | ", $output));
        }

        @unlink($inputPath);

        return $base64Image;
    }

    /**
     * Parses mosaic content to produce a list of required bricks
     *
     * @param int $idMosaic
     * @return array sorted list of bricks (size, color, count)
     */
    public function getBricksList($idMosaic) {
        $db = Db::getInstance();
        $stmt = $db->prepare("SELECT pavage FROM Mosaic WHERE id_Mosaic = ?");
        $stmt->execute([$idMosaic]);
        $res = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$res || empty($res['pavage'])) {
            return [];
        }

        $lines = explode("\n", $res['pavage']);
        $inventory = [];

        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) continue;
            
            $parts = explode(' ', $line);
            $key = $parts[0];
            
            if (strpos($key, '/') === false) continue; 

            if (!isset($inventory[$key])) {
                $inventory[$key] = 0;
            }
            $inventory[$key]++;
        }

        $finalList = [];
        foreach ($inventory as $key => $count) {
            list($size, $color) = explode('/', $key);
            
            if ($color[0] !== '#') $color = '#' . $color;

            $finalList[] = [
                'size' => $size,
                'color' => $color,
                'count' => $count
            ];
        }

        array_multisort(array_column($finalList, 'size'), SORT_DESC, $finalList);

        return $finalList;
    }

    /**
     * Converts the brick list into database records for order processing
     *
     * @param int $idMosaic
     * @return bool
     */
    public function saveMosaicComposition($idMosaic) {
        $bricks = $this->getBricksList($idMosaic);
        if (empty($bricks)) return false;
        $db = Db::getInstance();

        foreach ($bricks as $brick) {
            $idItem = $this->findItemId($brick['size'], $brick['color']);

            if ($idItem) {
                $sql = "INSERT IGNORE INTO MosaicComposition (id_Mosaic, id_Item, quantity_needed) VALUES (?, ?, ?)";
                $stmt = $db->prepare($sql);
                $stmt->execute([$idMosaic, $idItem, $brick['count']]);
            }
        }
        return true;
    }

    /**
     * Resolves specific item id based on brick characteristics
     *
     * @param string $size e.g., "2x4"
     * @param string $hexColor
     * @return int|false
     */
    private function findItemId($size, $hexColor) {
        $db = Db::getInstance();

        $cleanHex = str_replace('#', '', $hexColor);

        $dims = explode('x', $size);
        if (count($dims) < 2) return null;
        $w = (int)$dims[0];
        $l = (int)$dims[1];

        $sql = "SELECT I.id_Item 
                FROM Item I
                JOIN Shapes S ON I.shape_id = S.id_shape
                JOIN Colors C ON I.color_id = C.id_color
                WHERE C.hex_color = ? 
                AND (
                    (S.width = ? AND S.length = ?) 
                    OR 
                    (S.width = ? AND S.length = ?)
                )";
        
        $stmt = $db->prepare($sql);
        $stmt->execute([$cleanHex, $w, $l, $l, $w]);
        
        return $stmt->fetchColumn(); 
    }
    
    /**
     * Checks if the composition (recipe) for a mosaic has already been saved
     *
     * @param int $idMosaic
     * @return bool
     */
    public function hasComposition($idMosaic) {
        $db = Db::getInstance();
        $stmt = $db->prepare("SELECT 1 FROM MosaicComposition WHERE id_Mosaic = ? LIMIT 1");
        $stmt->execute([$idMosaic]);
        return (bool)$stmt->fetch();
    }

    /**
     * Calculates the price of a stored mosaic based on its raw cost metadata
     *
     * @param int $idMosaic
     * @return float
     */
    public function getMosaicPrice($idMosaic) {
        $db = Db::getInstance();
        $stmt = $db->prepare("SELECT pavage FROM Mosaic WHERE id_Mosaic = ?");
        $stmt->execute([$idMosaic]);
        $res = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$res || empty($res['pavage'])) {
            return 0.00;
        }

        $lines = explode("\n", $res['pavage']);
        $firstLine = trim($lines[0]);
        $parts = preg_split('/\s+/', $firstLine);

        $rawCost = 0.00;

        if (isset($parts[1]) && is_numeric($parts[1])) {
            $rawCost = (float) $parts[1];
        }

        if ($rawCost <= 0) {
            return 19.99;
        }

        $finalPrice = ($rawCost * self::MARGIN_COEFF) + self::HANDLING_FEE;

        return floor($finalPrice) + 0.99;
    }

    /**
     * Calculates price directly from raw content string without db lookup
     *
     * @param string $pavageContent
     * @return float
     */
    public function calculatePriceFromContent($pavageContent) {
        if (empty($pavageContent)) return 0.00;

        $lines = explode("\n", $pavageContent);
        $firstLine = trim($lines[0]);
        $parts = preg_split('/\s+/', $firstLine);

        $rawCost = (isset($parts[1]) && is_numeric($parts[1])) ? (float)$parts[1] : 0.00;
        if ($rawCost <= 0) return 19.99;

        $finalPrice = ($rawCost * self::MARGIN_COEFF) + self::HANDLING_FEE;

        return floor($finalPrice) + 0.99;
    }

    /**
     * Counts total bricks required based on raw content
     *
     * @param string $pavageContent
     * @return int
     */
    public function countPiecesFromContent($pavageContent) {
        if (empty($pavageContent)) {
            return 0;
        }

        $lines = explode("\n", $pavageContent);
        $count = 0;

        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) continue;
            if (strpos($line, '/') === false) continue;
            $count++;
        }

        return $count;
    }

    /**
     * Retrieves all mosaics associated with a specific order
     *
     * @param int $orderId
     * @return array
     */
    public function getMosaicsByOrderId($orderId) {
        $sql = "SELECT m.id_Mosaic, m.pavage, i.file, i.file_type 
                FROM Mosaic m
                LEFT JOIN CustomerImage i ON m.id_Image = i.id_Image
                WHERE m.id_Order = ?";
        
        $results = $this->requete($sql, [$orderId])->fetchAll();
        
        foreach ($results as $row) {
            if (!empty($row->file)) {
                $row->visuel = "data:" . $row->file_type . ";base64," . base64_encode($row->file);
            } else {
                $row->visuel = null;
            }
            $row->size = 64; 
            $row->style = 'Standard';
        }
        
        return $results;
    }

    /**
     * Generates html for a grid representation (used for quick preview)
     *
     * @param int $idMosaic
     * @return array|string html string and legend array
     */
    public function getMosaicGridHtml($idMosaic) {
        $db = \App\Core\Db::getInstance();
        $stmt = $db->prepare("SELECT pavage FROM Mosaic WHERE id_Mosaic = ?");
        $stmt->execute([$idMosaic]);
        $res = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$res || empty($res['pavage'])) return "Contenu introuvable";

        $lines = explode("\n", trim($res['pavage']));
        $bricksData = [];
        $maxX = 0; $maxY = 0;
        $colorToSymbol = [];
        $symbolIndex = 0;
        $symbols = range('A', 'Z');

        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line) || strpos($line, '/') === false) continue;

            $parts = preg_split('/\s+/', $line);
            $info = explode('/', $parts[0]);
            $color = "#" . $info[1];
            
            if (!isset($colorToSymbol[$color])) {
                $colorToSymbol[$color] = $symbols[$symbolIndex % 26] . (floor($symbolIndex / 26) ?: '');
                $symbolIndex++;
            }

            $size = explode('x', $info[0]);
            $w = (int)$size[0]; 
            $l = (int)$size[1];
            $x = (int)$parts[1]; 
            $y = (int)$parts[2];
            $rot = (int)($parts[3] ?? 0);

            $finalW = ($rot == 1) ? $l : $w;
            $finalH = ($rot == 1) ? $w : $l;

            $bricksData[] = [
                'x' => $x, 'y' => $y, 'w' => $finalW, 'h' => $finalH, 
                'color' => $color, 'symbol' => $colorToSymbol[$color]
            ];

            if ($x + $finalW > $maxX) $maxX = $x + $finalW;
            if ($y + $finalH > $maxY) $maxY = $y + $finalH;
        }

        $scale = 12; 
        $html = '<div style="position: relative; width: '.($maxX * $scale).'pt; height: '.($maxY * $scale).'pt; background: #ffffff; border: 1pt solid #333;">';
        
        foreach ($bricksData as $b) {
            $html .= '<div style="
                position: absolute;
                left: '.($b['x'] * $scale).'pt;
                top: '.($b['y'] * $scale).'pt;
                width: '.($b['w'] * $scale).'pt;
                height: '.($b['h'] * $scale).'pt;
                background-color: '.$b['color'].';
                border: 0.2pt solid rgba(0,0,0,0.4);
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 6pt;
                font-weight: bold;
                color: rgba(0,0,0,0.5);
                box-sizing: border-box;
                overflow: hidden;
            ">'.$b['symbol'].'</div>';
        }
        $html .= '</div>';

        return ['html' => $html, 'legend' => $colorToSymbol];
    }

    /**
     * Extracts detailed plan data for generating printable instructions
     *
     * @param int $idMosaic
     * @return array|null plan details
     */
    public function getMosaicPlanData($idMosaic) {
        $db = Db::getInstance();
        $stmt = $db->prepare("SELECT pavage FROM Mosaic WHERE id_Mosaic = ?");
        $stmt->execute([$idMosaic]);
        $res = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$res || empty($res['pavage'])) return null;

        $lines = explode("\n", trim($res['pavage']));
        
        $bricks = [];
        $maxX = 0; 
        $maxY = 0;
        
        $colorToSymbol = [];
        $symbols = range('A', 'Z');
        $symbolIndex = 0;

        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line) || strpos($line, '/') === false) continue;

            $parts = preg_split('/\s+/', $line);
            $info = explode('/', $parts[0]);
            
            $colorHex = "#" . $info[1];
            
            if (!isset($colorToSymbol[$colorHex])) {
                $suffix = floor($symbolIndex / 26) > 0 ? floor($symbolIndex / 26) : '';
                $colorToSymbol[$colorHex] = $symbols[$symbolIndex % 26] . $suffix;
                $symbolIndex++;
            }

            $size = explode('x', $info[0]);
            $w = (int)$size[0];
            $h = (int)$size[1];
            
            $x = (int)$parts[1]; 
            $y = (int)$parts[2];
            $rot = (int)($parts[3] ?? 0);

            $finalW = ($rot == 1) ? $h : $w;
            $finalH = ($rot == 1) ? $w : $h;

            $bricks[] = [
                'x' => $x,
                'y' => $y,
                'w' => $finalW,
                'h' => $finalH,
                'color' => $colorHex,
                'symbol' => $colorToSymbol[$colorHex]
            ];

            if ($x + $finalW > $maxX) $maxX = $x + $finalW;
            if ($y + $finalH > $maxY) $maxY = $y + $finalH;
        }

        return [
            'width' => $maxX,
            'height' => $maxY,
            'bricks' => $bricks,
            'legend' => $colorToSymbol
        ];
    }

    /**
     * Updates the temporary input file with current stock levels for java processing
     *
     * @param string $filePath
     * @return void
     */
    private function updateBriquesFile($filePath) {
        $stockModel = new StockModel();
        $items = $stockModel->getFullStockDetails(); 

        $shapesList = [];
        $colorsList = [];
        
        $shapeMap = []; 
        $colorMap = []; 

        $brickLines = [];

        foreach ($items as $item) {
            $shapeDef = $item['width'] . '-' . $item['length'];
            if (!empty($item['hole'])) {
                $shapeDef .= '-' . $item['hole'];
            }

            if (!isset($shapeMap[$shapeDef])) {
                $shapeMap[$shapeDef] = count($shapesList);
                $shapesList[] = $shapeDef;
            }
            $sIdx = $shapeMap[$shapeDef];

            $cId = $item['id_color'];
            $cHex = str_replace('#', '', $item['hex_color']);

            if (!isset($colorMap[$cId])) {
                $colorMap[$cId] = count($colorsList);
                $colorsList[] = $cHex; 
            }
            $cIdx = $colorMap[$cId];

            $price = $item['price'];
            $qty = max(0, intval($item['current_stock']));
            
            $brickLines[] = "$sIdx/$cIdx $price $qty";
        }
        
        $content = "";
        
        $content .= count($shapesList) . " " . count($colorsList) . " " . count($brickLines) . "\n";

        foreach ($shapesList as $s) {
            $content .= "$s\n";
        }

        foreach ($colorsList as $c) {
            $content .= "$c\n";
        }

        foreach ($brickLines as $line) {
            $content .= "$line\n";
        }

        file_put_contents($filePath, $content);
    }

    /**
     * Reduces inventory count when a mosaic is finalized
     *
     * @param int $idMosaic
     * @return void
     */
    public function deductStockFromMosaic($idMosaic) {
        $stockModel = new StockModel();

        $sql = "SELECT id_Item, quantity_needed FROM MosaicComposition WHERE id_Mosaic = ?";
        $items = $this->requete($sql, [$idMosaic])->fetchAll();
        
        if (!$items) return;

        foreach ($items as $item) {
            $qtyToRemove = -1 * abs($item->quantity_needed);
            $stockModel->updateStock($item->id_Item, $qtyToRemove);
        }
    }
}