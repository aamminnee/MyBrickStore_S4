<link href="https://fonts.googleapis.com/css2?family=Fredoka:wght@400;600;700&family=Inter:wght@400;500;700&display=swap" rel="stylesheet">
<div class="team-page-container">
    
    <?php // agency presentation section ?>
    <section class="agency-section">
        <h1 class="page-title">AZER TY</h1>
        <p class="agency-description">
            Nous sommes une agence web spécialisée dans le développement sur mesure, le design UX/UI et la création de plateformes digitales performantes. Notre mission est d'accompagner votre entreprise dans sa transformation numérique avec des solutions innovantes, robustes et adaptées à vos besoins.
        </p>
        <a href="https://www.azerty.com" target="_blank" class="agency-link">Visiter le site de notre agence</a>
    </section>

    <?php // technical team section ?>
    <section class="technical-team-section">
        <h2 class="section-title">Notre équipe technique</h2>
        
        <div class="team-grid">
            
            <?php // team member 1 - team leader ?>
            <div class="team-member-card">
                <div class="member-photo">
                    <img src="<?= $_ENV['BASE_URL'] ?>/img/amine.jpeg" alt="Amine AISSYNE">
                </div>
                <h3 class="member-name">Amine AISSYNE</h3>
                <div class="member-role">Chef d'équipe & Dev Full Stack</div>
                <a href="#" target="_blank" class="portfolio-btn">Mon Portfolio</a>
                <p class="member-description">Amine supervise l'architecture globale du projet et coordonne l'équipe technique pour assurer la livraison des fonctionnalités.</p>
            </div>

            <?php // team member 2 - co-leader ?>
            <div class="team-member-card">
                <div class="member-photo">
                    <img src="<?= $_ENV['BASE_URL'] ?>/img/amine.jpeg" alt="Zhabrail ALKHASTOV">
                </div>
                <h3 class="member-name">Zhabrail ALKHASTOV</h3>
                <div class="member-role">Co-chef d'équipe & Dev Full Stack</div>
                <a href="https://alkzhab.github.io/" target="_blank" class="portfolio-btn">Mon Portfolio</a>
                <p class="member-description">Zhabrail apporte son expertise technique et soutient la gestion de l'équipe pour garantir la qualité du code et la sécurité.</p>
            </div>

            <?php // team member 3 - full stack dev ?>
            <div class="team-member-card">
                <div class="member-photo">
                    <img src="<?= $_ENV['BASE_URL'] ?>/img/amine.jpeg" alt="ESSAIDI Rayan">
                </div>
                <h3 class="member-name">ESSAIDI Rayan</h3>
                <div class="member-role">Dev Full Stack</div>
                <a href="#" target="_blank" class="portfolio-btn">Mon Portfolio</a>
                <p class="member-description">Rayan intervient sur l'ensemble de la chaîne de développement, de la base de données à l'interface utilisateur.</p>
            </div>

            <?php // team member 4 - full stack dev ?>
            <div class="team-member-card">
                <div class="member-photo">
                    <img src="<?= $_ENV['BASE_URL'] ?>/img/amine.jpeg" alt="POLIN Ethan">
                </div>
                <h3 class="member-name">POLIN Ethan</h3>
                <div class="member-role">Dev Full Stack</div>
                <a href="#" target="_blank" class="portfolio-btn">Mon Portfolio</a>
                <p class="member-description">Ethan est responsable du développement des fonctionnalités interactives et de l'optimisation des performances du site.</p>
            </div>
            
        </div>
    </section>
</div>