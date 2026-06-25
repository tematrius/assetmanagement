<div class="page-heading">
    <div>
        <h2>Mon profil</h2>
        <p>Informations utilisees pour les attributions et le circuit de validation.</p>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-8">
        <section class="card p-3">
            <h3 class="section-title">Identite professionnelle</h3>
            <div class="profile-grid">
                <div><span>Nom complet</span><strong><?= e((string) $profile['nom']) ?></strong></div>
                <div><span>PF</span><strong><?= e((string) ($profile['matricule'] ?? '-')) ?></strong></div>
                <div><span>Email</span><strong><?= e((string) ($profile['email'] ?? '-')) ?></strong></div>
                <div><span>Telephone</span><strong><?= e((string) ($profile['telephone'] ?? '-')) ?></strong></div>
                <div><span>Direction</span><strong><?= e((string) ($profile['direction'] ?? '-')) ?></strong></div>
                <div><span>Departement</span><strong><?= e((string) ($profile['departement'] ?? '-')) ?></strong></div>
                <div><span>Service</span><strong><?= e((string) ($profile['service'] ?? '-')) ?></strong></div>
                <div><span>Agence</span><strong><?= e((string) ($profile['site'] ?? '-')) ?></strong></div>
                <div><span>Fonction</span><strong><?= e((string) $profile['fonction_metier']) ?></strong></div>
                <div><span>Role applicatif</span><strong><?= e((string) $profile['role_systeme']) ?></strong></div>
            </div>
        </section>
    </div>
    <div class="col-lg-4">
        <section class="card p-3">
            <h3 class="section-title">Circuit de validation</h3>
            <?php foreach ($validators as $validator): ?>
                <div class="validator-row">
                    <strong><?= e((string) $validator['nom']) ?></strong>
                    <span><?= e((string) $validator['fonction_metier']) ?></span>
                    <small><?= e((string) ($validator['departement'] ?? '')) ?></small>
                </div>
            <?php endforeach; ?>
            <?php if ($validators === []): ?>
                <p class="text-muted mb-0">Aucun responsable validateur configure.</p>
            <?php endif; ?>
        </section>
    </div>
</div>
