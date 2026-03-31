<link href="https://fonts.googleapis.com/css2?family=Fredoka:wght@400;600;700&family=Inter:wght@400;500;700&display=swap" rel="stylesheet">

<div class="team-page-container">
    
    <section class="agency-section">
        <h1 class="page-title">AZER TY</h1>
        <p class="agency-description">
            <?= $t['team_agency_desc'] ?? 'Nous sommes une agence web spécialisée dans le développement sur mesure, le design UX/UI et la création de plateformes digitales performantes.' ?>
        </p>
        <a href="https://www.azerty.com" target="_blank" class="agency-link">
            <?= $t['team_agency_link'] ?? 'Visiter le site de notre agence' ?>
        </a>
    </section>

    <section class="technical-team-section">
        <h2 class="section-title"><?= $t['team_section_title'] ?? 'Notre équipe technique' ?></h2>
        
        <div class="team-grid">
            
            <div class="team-member-card">
                <div class="member-photo">
                    <img src="<?= $_ENV['BASE_URL'] ?>/img/amine.jpeg" alt="Amine AISSYNE">
                </div>
                <h3 class="member-name">Amine AISSYNE</h3>
                <div class="member-role"><?= $t['team_role_leader'] ?? 'Chef d\'équipe & Dev Full Stack' ?></div>
                <a href="#" target="_blank" class="portfolio-btn"><?= $t['team_btn_portfolio'] ?? 'Mon Portfolio' ?></a>
                <p class="member-description"><?= $t['team_desc_amine'] ?? 'Amine supervise l\'architecture globale du projet...' ?></p>
            </div>

            <div class="team-member-card">
                <div class="member-photo">
                    <img src="<?= $_ENV['BASE_URL'] ?>/img/zhabrail.png" alt="Zhabrail ALKHASTOV">
                </div>
                <h3 class="member-name">Zhabrail ALKHASTOV</h3>
                <div class="member-role"><?= $t['team_role_coleader'] ?? 'Co-chef d\'équipe & Dev Full Stack' ?></div>
                <a href="https://alkzhab.github.io/" target="_blank" class="portfolio-btn"><?= $t['team_btn_portfolio'] ?? 'Mon Portfolio' ?></a>
                <p class="member-description"><?= $t['team_desc_zhabrail'] ?? 'Zhabrail apporte son expertise technique...' ?></p>
            </div>

            <div class="team-member-card">
                <div class="member-photo">
                    <img src="<?= $_ENV['BASE_URL'] ?>/img/ethan.png" alt="Ethan POLIN">
                </div>
                <h3 class="member-name">Ethan POLIN</h3>
                <div class="member-role"><?= $t['team_role_dev'] ?? 'Dev Full Stack' ?></div>
                <a href="#" target="_blank" class="portfolio-btn"><?= $t['team_btn_portfolio'] ?? 'Mon Portfolio' ?></a>
                <p class="member-description"><?= $t['team_desc_ethan'] ?? 'Ethan est responsable du développement...' ?></p>
            </div>

            <div class="team-member-card">
                <div class="member-photo">
                    <img src="<?= $_ENV['BASE_URL'] ?>/img/rayan.png" alt="Rayan ESSAIDI">
                </div>
                <h3 class="member-name">Rayan ESSAIDI</h3>
                <div class="member-role"><?= $t['team_role_dev'] ?? 'Dev Full Stack' ?></div>
                <a href="https://rayan-essaidi.alwaysdata.net/" target="_blank" class="portfolio-btn"><?= $t['team_btn_portfolio'] ?? 'Mon Portfolio' ?></a>
                <p class="member-description"><?= $t['team_desc_rayan'] ?? 'Rayan intervient sur l\'ensemble de la chaîne...' ?></p>
            </div>
            
        </div>
    </section>
</div>