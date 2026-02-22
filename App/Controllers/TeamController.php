<?php
namespace App\Controllers;

use App\Core\Controller;

/**
 * controller for the team and contact page
 */
class TeamController extends Controller
{
    /**
     * display the technical team page
     *
     * @return void
     */
    public function index()
    {
        // set the page title
        $titre = "Équipe Technique - MyBrickStore";

        // define the css file to be loaded
        $css = "team_views.css";

        // render the view for the team page with title and css
        // we add 'css' to the compact function
        $this->render('team_views', compact('titre', 'css'));
    }
}