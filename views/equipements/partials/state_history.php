<?php
// Display state change history for equipment
$stateHistory = $state_history ?? [];
?>

<div class="card mb-3">
    <div class="card-header bg-light">
        <h6 class="mb-0"><i class="bi bi-circle-fill"></i> Historique des changements d'état</h6>
    </div>
    <div class="card-body">
        <?php if (empty($stateHistory)): ?>
            <p class="text-muted mb-0">Aucun changement d'état enregistré.</p>
        <?php else: ?>
            <div class="timeline">
                <?php foreach ($stateHistory as $entry): ?>
                    <div class="timeline-item mb-3">
                        <div class="d-flex gap-3">
                            <div style="flex-shrink: 0; width: 100px;">
                                <small class="text-muted d-block">
                                    <?php 
                                    try {
                                        $date = new DateTime($entry['created_at']);
                                        echo $date->format('d/m/Y');
                                        echo '<br>';
                                        echo $date->format('H:i');
                                    } catch (Exception $e) {
                                        echo '-';
                                    }
                                    ?>
                                </small>
                            </div>
                            <div style="flex-grow: 1;">
                                <div class="mb-2">
                                    <span class="badge <?= state_badge_class($entry['ancien_etat'] ?? 'bon') ?>">
                                        <?= state_label($entry['ancien_etat'] ?? 'bon') ?>
                                    </span>
                                    <i class="bi bi-arrow-right mx-2"></i>
                                    <span class="badge <?= state_badge_class($entry['nouvel_etat'] ?? 'bon') ?>">
                                        <?= state_label($entry['nouvel_etat'] ?? 'bon') ?>
                                    </span>
                                </div>
                                <div class="small">
                                    <strong class="text-primary"><?= e($entry['agent_username'] ?? 'Système') ?></strong>
                                </div>
                                <?php if (!empty($entry['commentaire'])): ?>
                                    <div class="bg-light p-2 rounded small mt-2" style="border-left: 3px solid #0d6efd;">
                                        <strong>Motif:</strong><br>
                                        <?= e($entry['commentaire']) ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php if ($entry !== end($stateHistory)): ?>
                        <div class="text-center text-muted" style="margin: 10px 0; height: 20px;">
                            <i class="bi bi-chevron-down" style="color: #ccc;"></i>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
