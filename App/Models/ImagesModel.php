<?php
namespace App\Models;

use App\Core\Model;
use App\Core\Db;
use PDO;
use PDOException;

/**
 * Class ImagesModel
 * 
 ** Manages user-uploaded images stored as blobs in the database
 ** Handles the separation between metadata (image table) and binary data (customerimage table)
 * 
 * @package App\Models
 */
class ImagesModel extends Model {

    /** @var string The database table associated with the model. */
    protected $table = 'Image';

    /**
     * Saves a new image by creating linked records in both image tables
     *
     * @param int $idCustomer owner identifier
     * @param string $imgData binary content of the file
     * @param string $fileName original name of the file
     * @param string $mimeType mime type (e.g. image/png)
     * @return int the new image identifier
     * @throws PDOException if the transaction fails
     */
    public function saveCustomerImage($idCustomer, $imgData, $fileName, $mimeType) {
        $db = Db::getInstance();

        try {
            $db->beginTransaction();

            $sqlImage = "INSERT INTO Image (filename, id_Customer) VALUES (?, ?)";
            $stmt = $db->prepare($sqlImage);
            $stmt->execute([$fileName, $idCustomer]);

            $idImage = $db->lastInsertId();

            $sqlCustomer = "INSERT INTO CustomerImage (id_Image, file, file_type) VALUES (?, ?, ?)";
            $stmt2 = $db->prepare($sqlCustomer);
            
            $stmt2->bindValue(1, $idImage);
            $stmt2->bindValue(2, $imgData, PDO::PARAM_STR);
            $stmt2->bindValue(3, $mimeType);
            
            $stmt2->execute();

            $db->commit();

            return $idImage;

        } catch (PDOException $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Updates the binary content of an existing image (e.g. after cropping)
     *
     * @param int $idImage
     * @param int $idCustomer used for ownership verification
     * @param string $newData new binary data
     * @return bool success status
     */
    public function updateCustomerImageBlob($idImage, $idCustomer, $newData) {
        $db = Db::getInstance();

        if ($idCustomer === null) {
            $sql = "UPDATE CustomerImage c
                INNER JOIN Image i ON c.id_Image = i.id_Image
                SET c.file = ?
                WHERE c.id_Image = ? AND i.id_Customer IS NULL";
        
            $stmt = $db->prepare($sql);
            $stmt->bindValue(1, $newData, PDO::PARAM_STR);
            $stmt->bindValue(2, $idImage, PDO::PARAM_INT);
            return $stmt->execute();
        } else {
        
            $sql = "UPDATE CustomerImage c
                    INNER JOIN Image i ON c.id_Image = i.id_Image
                    SET c.file = ?
                    WHERE c.id_Image = ? AND i.id_Customer = ?";
                    
            $stmt = $db->prepare($sql);
            $stmt->bindValue(1, $newData, PDO::PARAM_STR);
            $stmt->bindValue(2, $idImage, PDO::PARAM_INT);
            $stmt->bindValue(3, $idCustomer, PDO::PARAM_INT);
            
            return $stmt->execute();
        }
    }

    /**
     * Retrieves image data and metadata by id
     *
     * @param int $id image identifier
     * @param int|null $userId optional user id for strict ownership check
     * @return mixed image object or false
     */
    public function getImageById($id, $userId = null) {
        $sql = "SELECT i.id_Image, i.filename, i.id_Customer, c.file, c.file_type 
                FROM Image i
                JOIN CustomerImage c ON i.id_Image = c.id_Image
                WHERE i.id_Image = ?";
        
        $params = [$id];

        if ($userId !== null) {
            $sql .= " AND i.id_Customer = ?";
            $params[] = $userId;
        }
        
        return $this->requete($sql, $params)->fetch();
    }

    /**
     * Fetches the most recently uploaded image for a specific user
     *
     * @param int $userId
     * @return mixed image object or false
     */
    public function getLastImageByUserId($userId) {
        $sql = "SELECT i.id_Image, i.filename, i.id_Customer, c.file, c.file_type 
                FROM Image i
                JOIN CustomerImage c ON i.id_Image = c.id_Image
                WHERE i.id_Customer = ? 
                ORDER BY i.id_Image DESC 
                LIMIT 1";
        
        return $this->requete($sql, [$userId])->fetch();
    }

    /**
     * Assigns an orphaned image (guest upload) to a registered user
     *
     * @param int $imageId
     * @param int $userId
     * @return bool
     */
    public function assignImageToUser($imageId, $userId) {
        $db = Db::getInstance();
        $sql = "UPDATE Image SET id_Customer = ? WHERE id_Image = ? AND id_Customer IS NULL";
        $stmt = $db->prepare($sql);
        return $stmt->execute([$userId, $imageId]);
    }
}

