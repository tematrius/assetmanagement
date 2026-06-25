<!-- Modal: Change Equipment State -->
<div class="modal fade" id="changeStateModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Modifier l'état de l'équipement</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="<?= e(base_url('equipements/' . $equipement['id'] . '/change-state')) ?>">
                <?= csrf_field() ?>
                <div class="modal-body">
                    <input type="hidden" name="equipement_id" value="<?= (int) $equipement['id'] ?>">
                    
                    <div class="mb-3">
                        <label for="nouvelEtat" class="form-label">Nouvel état</label>
                        <select class="form-select" id="nouvelEtat" name="nouvel_etat" required>
                            <option value="">-- Sélectionner un état --</option>
                            <option value="neuf">Neuf</option>
                            <option value="bon">Bon</option>
                            <option value="moyen">Moyen</option>
                            <option value="mauvais">Mauvais</option>
                            <option value="declasse">Déclassé</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="commentaire" class="form-label">Commentaire <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="commentaire" name="commentaire" rows="3" 
                                  placeholder="Expliquez le motif du changement d'état..." required></textarea>
                        <small class="text-muted">Le commentaire est obligatoire pour l'audit</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary">Confirmer le changement</button>
                </div>
            </form>
        </div>
    </div>
</div>
