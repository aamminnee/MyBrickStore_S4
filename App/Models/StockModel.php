<?php
namespace App\Models;

use App\Core\Model;
use App\Core\Db;
use PDO;

/**
 * Class StockModel
 * 
 ** Manages the inventory of lego parts
 ** Uses only stockentry to calculate levels (sum of imports and sales)
 *
 * @package App\Models
 */
class StockModel extends Model {

    /** @var string The database table associated with the model. */
    protected $table = 'Item';

    /**
     * Retrieves a paginated list of stock items with optional filters
     *
     * @param int $limit number of items per page
     * @param int $page current page number
     * @param string|null $shapeFilter filter by shape name
     * @param string|null $colorFilter filter by color name
     * @param string $statusFilter filter by stock level ('all', 'low', 'critical')
     * @return array list of items
     */
    public function getPaginatedStock($limit, $page, $shapeFilter = null, $colorFilter = null, $statusFilter = 'all') {
        $offset = ($page - 1) * $limit;
        $params = [];
        $whereClause = "";

        if (!empty($shapeFilter)) {
            $whereClause .= " AND s.name = :shape";
            $params[':shape'] = $shapeFilter;
        }
        if (!empty($colorFilter)) {
            $whereClause .= " AND c.name = :color";
            $params[':color'] = $colorFilter;
        }

        if ($statusFilter === 'low') {
            $whereClause .= " AND IFNULL(e.current_stock, 0) < 50";
        } elseif ($statusFilter === 'critical') {
            $whereClause .= " AND IFNULL(e.current_stock, 0) < 0";
        }

        $sql = "SELECT 
                    i.id_Item, 
                    s.name AS shape_name, 
                    c.name AS color_name,
                    c.hex_color,
                    i.price,
                    IFNULL(e.current_stock, 0) AS current_stock
                FROM Item i
                JOIN Shapes s ON i.shape_id = s.id_shape
                JOIN Colors c ON i.color_id = c.id_color
                LEFT JOIN (
                    SELECT id_Item, SUM(quantity) AS current_stock 
                    FROM StockEntry 
                    GROUP BY id_Item
                ) e ON i.id_Item = e.id_Item
                WHERE 1=1 $whereClause
                ORDER BY s.name, c.name
                LIMIT :limit OFFSET :offset";

        $db = Db::getInstance();
        $stmt = $db->prepare($sql);

        foreach ($params as $key => $val) {
            $stmt->bindValue($key, $val);
        }

        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);

        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Counts total items matching the current filters for pagination
     *
     * @param string|null $shapeFilter
     * @param string|null $colorFilter
     * @param string $statusFilter
     * @return int total count
     */
    public function countStockItems($shapeFilter = null, $colorFilter = null, $statusFilter = 'all') {
        $params = [];
        $whereClause = "";

        if (!empty($shapeFilter)) {
            $whereClause .= " AND s.name = ?";
            $params[] = $shapeFilter;
        }
        if (!empty($colorFilter)) {
            $whereClause .= " AND c.name = ?";
            $params[] = $colorFilter;
        }

        if ($statusFilter === 'low') {
            $whereClause .= " AND IFNULL(e.current_stock, 0) < 50";
        } elseif ($statusFilter === 'critical') {
            $whereClause .= " AND IFNULL(e.current_stock, 0) < 0";
        }

        $sql = "SELECT COUNT(*) as total
                FROM Item i
                JOIN Shapes s ON i.shape_id = s.id_shape
                JOIN Colors c ON i.color_id = c.id_color
                LEFT JOIN (
                    SELECT id_Item, SUM(quantity) AS current_stock 
                    FROM StockEntry 
                    GROUP BY id_Item
                ) e ON i.id_Item = e.id_Item
                WHERE 1=1 $whereClause";

        $res = $this->requete($sql, $params)->fetch();
        return $res->total;
    }

    /**
     * Retrieves all items formatted for a search dropdown
     *
     * @return array
     */
    public function getAllItemsForSearch() {
        $sql = "SELECT 
                    i.id_Item, 
                    CONCAT(s.name, ' - ', c.name) AS label 
                FROM Item i
                JOIN Shapes s ON i.shape_id = s.id_shape
                JOIN Colors c ON i.color_id = c.id_color
                ORDER BY s.name, c.name";
        
        return Db::getInstance()->query($sql)->fetchAll();
    }

    /**
     * Fetches distinct shapes for filter dropdowns
     *
     * @return array
     */
    public function getAllShapes() {
        return Db::getInstance()->query("SELECT DISTINCT name FROM Shapes ORDER BY name")->fetchAll();
    }

    /**
     * Fetches distinct colors for filter dropdowns
     *
     * @return array
     */
    public function getAllColors() {
        return Db::getInstance()->query("SELECT DISTINCT name FROM Colors ORDER BY name")->fetchAll();
    }

    /**
     * Records a manual stock adjustment (positive or negative)
     *
     * @param int $itemId
     * @param int $quantity
     * @return mixed
     */
    public function updateStock($itemId, $quantity){
        $sql = "INSERT INTO StockEntry (id_Item, quantity) VALUES (?, ?)";
        return $this->requete($sql, [$itemId, $quantity]);
    }

    /**
     * Counts items below a certain stock threshold for dashboard alerts
     *
     * @param int $threshold default 50
     * @return int
     */
    public function countLowStockItems($threshold = 50) {
        $sql = "SELECT COUNT(*) as total FROM (
                    SELECT 
                        IFNULL(e.current_stock, 0) AS current_stock
                    FROM Item i
                    LEFT JOIN (
                        SELECT id_Item, SUM(quantity) AS current_stock 
                        FROM StockEntry 
                        GROUP BY id_Item
                    ) e ON i.id_Item = e.id_Item
                ) as real_stock
                WHERE current_stock < ?";
                
        $stmt = \App\Core\Db::getInstance()->prepare($sql);
        $stmt->execute([$threshold]);
        $res = $stmt->fetch();
        return $res->total ?? 0;
    }

    /**
     * Exports full inventory details for external processing or reporting
     *
     * @return array
     */
    public function getFullStockDetails() {
        $sql = "SELECT 
                    s.width, 
                    s.length, 
                    s.hole,
                    c.id_color,     
                    c.hex_color,
                    i.price,
                    IFNULL(e.current_stock, 0) AS current_stock
                FROM Item i
                JOIN Shapes s ON i.shape_id = s.id_shape
                JOIN Colors c ON i.color_id = c.id_color
                LEFT JOIN (
                    SELECT id_Item, SUM(quantity) AS current_stock 
                    FROM StockEntry 
                    GROUP BY id_Item
                ) e ON i.id_Item = e.id_Item";
        
        return \App\Core\Db::getInstance()->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Retrieves the text reference (Shape/Color) expected by Java for a given ID.
     * @param int $idItem
     * @return string|null Format "ShapeName/HexColor" (ex: "2-2/c9cae2")
     */
    public function getItemReferenceById($idItem) {
        $sql = "SELECT s.name, c.hex_color 
                FROM Item i
                JOIN Shapes s ON i.shape_id = s.id_shape
                JOIN Colors c ON i.color_id = c.id_color
                WHERE i.id_Item = ?";
        
        $row = $this->requete($sql, [$idItem])->fetch();
        
        if ($row) {
            $colorClean = strtolower(str_replace('#', '', $row->hex_color));
            return $row->name . '/' . $colorClean;
        }
        return null;
    }
}