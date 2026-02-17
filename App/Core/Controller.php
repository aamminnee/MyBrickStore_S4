<?php
namespace App\Core;

use App\Models\TranslationModel;

/**
 * Abstract Class Controller
 *
 ** Base controller that all application controllers must extend.
 ** Handles common initialization tasks such as Session start and Translation loading.
 ** Provides the 'render' method to generate views.
 *
 * @package App\Core
 */
abstract class Controller {

    /** @var array Holds translation strings for the current language. */
    protected $trans = [];

    /**
     * Constructor.
     * Ensures session is started and loads translations based on user preference.
     */
    public function __construct() {
        if (session_status() === PHP_SESSION_NONE) session_start();

        $lang = $_SESSION['lang'] ?? 'fr';

        if (class_exists('\\App\\Models\\TranslationModel')) {
            $translationModel = new TranslationModel();
            $this->trans = $translationModel->getTranslations($lang);
        }
    }

    /**
     * Renders a view file within a layout template.
     *
     * @param string $file The name of the view file (without .php extension).
     * @param array $data Associative array of data to pass to the view.
     * @param string $template The layout template to use (default: 'default').
     * @return void
     */
    public function render(string $file, array $data = [], string $template = 'default') {

        if (!isset($data['t'])) {
            $data['t'] = $this->trans;
        }

        $data['trans'] = $this->trans;

        extract($data);
        ob_start();

        require_once ROOT . '/App/Views/' . $file . '.php';
        $content = ob_get_clean();
        require_once ROOT . '/App/Views/' . $template . '.php';
    }
}