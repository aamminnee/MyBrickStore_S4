<?php
namespace App\Models;

use App\Core\Model;

/**
 * Class TranslationModel
 * 
 ** Manages the retrieval of localized text strings from the database
 ** Supports dynamic switching between languages (e.g., fr/en)
 * 
 * @package App\Models
 */
class TranslationModel extends Model {

    /**
     * Retrieves all translation pairs for a specific language
     *
     * @param string $lang the language code (e.g., 'fr', 'en')
     * @return array associative array where keys are text identifiers
     */
    public function getTranslations($lang) {
        $sql = "SELECT key_name, texte FROM Translations WHERE lang = ?";
        $results = $this->requete($sql, [$lang])->fetchAll();
        $translations = [];
        foreach ($results as $row) {
            $translations[$row->key_name] = $row->texte;
        }
        return $translations;
    }
}