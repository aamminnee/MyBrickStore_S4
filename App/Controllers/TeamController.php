<?php
namespace App\Controllers;

use App\Core\Controller;

/**
 * Class TeamController
 *
 * * Controller for the team and contact page
 *
 * @package App\Controllers
 */
class TeamController extends Controller {
    /**
     * display the technical team page
     *
     * @return void
     */
    public function index() {
        $titre = "Équipe Technique - MyBrickStore";
        $css = "team_views.css";
        $this->render('team_views', compact('titre', 'css'));
    }
}