<?php
// Display current equipment state with change button
?>

<div class="card mt-3">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h6 class="mb-0">État actuel</h6>
        <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#changeStateModal">
            <i class="bi bi-pencil"></i> Modifier l'état
        </button>
    </div>
    <div class="card-body">
        <div class="d-flex align-items-center gap-3">
            <div>
                <small class="text-muted d-block">État réel</small>
                <strong><?= state_badge($equipement['etat'] ?? 'bon') ?></strong>
            </div>
            <div>
                <small class="text-muted d-block">État théorique</small>
                <strong><?= state_badge($equipement['etat_theorique'] ?? 'bon') ?></strong>
            </div>
        </div>
        <div class="mt-3">
            <button type="button" class="btn btn-primary w-100" data-bs-toggle="modal" data-bs-target="#changeStateModal">
                Modifier l'état
            </button>
        </div>
    </div>
</div>
