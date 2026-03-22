<?php
namespace App\Controllers;

use App\Core\Controller;

/**
 * controller for the mybrickgame promotion page
 */
class GameController extends Controller
{
    /**
     * displays the promotion page and loads the points policy
     *
     * @return void
     */
    public function index(): void
    {
        // define the paths to look for the points policy json file
        $configPath = __DIR__ . '/../../config/pointsPolicy.json';
        $rootPath = __DIR__ . '/../../pointsPolicy.json';
        
        $pointsPolicy = [];
        
        // try to load the policy from config folder, fallback to root folder
        if (file_exists($configPath)) {
            $jsonContent = file_get_contents($configPath);
            $pointsPolicy = json_decode($jsonContent, true);
        } elseif (file_exists($rootPath)) {
            $jsonContent = file_get_contents($rootPath);
            $pointsPolicy = json_decode($jsonContent, true);
        }

        // get the game url from environment variables (fallback to localhost if missing)
        $gameUrl = $_ENV['MYBRICKGAME'];

        // pass data to the view, which will be injected into default.php
        $this->render('game_promo_views', [
            'policy' => $pointsPolicy,
            'gameUrl' => $gameUrl,
            'css' => 'game_promo_views.css'
        ]);
    }
}