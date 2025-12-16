<?php
/**
 * Lair Card Creation Form
 * Create a horizontal landscape card for lair actions
 */
?>
<?php require_once ROOT . '/src/views/templates/header.php'; ?>
<?php require_once ROOT . '/src/views/templates/navbar.php'; ?>

<main class="container mt-5">
    <h2 class="mb-4">Create Lair Action Card</h2>
    
    <p class="text-muted mb-4">
        Create a horizontal landscape card for lair actions. This card displays the lair's special actions that occur on initiative count 20 (losing initiative ties) and regional effects around the lair.
    </p>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <strong>Errors found:</strong>
            <ul class="mb-0">
                <?php foreach ($errors as $field => $message): ?>
                    <li><?php echo htmlspecialchars($message); ?></li>
                <?php endforeach; ?>
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <form action="index.php?url=lair-card-store" method="POST" enctype="multipart/form-data">
        
        <!-- BASIC INFORMATION -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Basic Information</h5>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label for="monster_name" class="form-label">Monster Name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="monster_name" name="monster_name" 
                           required value="<?php echo htmlspecialchars($old['monster_name'] ?? ''); ?>"
                           placeholder="e.g., Ancient Red Dragon">
                    <small class="text-muted">The name of the monster this lair belongs to</small>
                </div>

                <div class="mb-3">
                    <label for="lair_name" class="form-label">Lair Name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="lair_name" name="lair_name" 
                           required value="<?php echo htmlspecialchars($old['lair_name'] ?? ''); ?>"
                           placeholder="e.g., Volcanic Lair, Sunken Temple">
                    <small class="text-muted">Descriptive name of the lair location</small>
                </div>

                <div class="mb-3">
                    <label for="lair_description" class="form-label">Lair Description</label>
                    <textarea class="form-control" id="lair_description" name="lair_description" rows="3"
                              placeholder="Describe the lair's environment, atmosphere, and notable features..."><?php echo htmlspecialchars($old['lair_description'] ?? ''); ?></textarea>
                </div>

                <div class="mb-3">
                    <label for="lair_initiative" class="form-label">Initiative Count</label>
                    <input type="number" class="form-control" id="lair_initiative" name="lair_initiative" 
                           value="<?php echo (int)($old['lair_initiative'] ?? 20); ?>" min="1" max="30">
                    <small class="text-muted">Initiative count when lair actions occur (typically 20)</small>
                </div>
            </div>
        </div>

        <!-- LAIR ACTIONS -->
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Lair Actions</h5>
                <button type="button" class="btn btn-sm btn-primary" onclick="addLairAction()">
                    <i class="fa-solid fa-plus"></i> Add Action
                </button>
            </div>
            <div class="card-body">
                <p class="text-muted small">
                    On initiative count 20 (losing initiative ties), the lair can take one of these actions.
                </p>

                <div id="lair-actions-container">
                    <!-- Lair action entries will be added here -->
                    <div class="lair-action-entry mb-3">
                        <div class="row">
                            <div class="col-md-4">
                                <input type="text" class="form-control" name="lair_action_name[]" 
                                       placeholder="Action Name" required>
                            </div>
                            <div class="col-md-8">
                                <div class="input-group">
                                    <textarea class="form-control" name="lair_action_description[]" 
                                              rows="2" placeholder="Action description..." required></textarea>
                                    <button type="button" class="btn btn-outline-danger" onclick="this.closest('.lair-action-entry').remove()">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- REGIONAL EFFECTS -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Regional Effects</h5>
            </div>
            <div class="card-body">
                <p class="text-muted small">
                    Effects that occur in the region around the lair (typically within 1-6 miles). These effects end if the creature dies.
                </p>
                <textarea class="form-control" id="regional_effects" name="regional_effects" rows="5"
                          placeholder="Describe regional effects around the lair..."><?php echo htmlspecialchars($old['regional_effects'] ?? ''); ?></textarea>
                <small class="text-muted">
                    Example: "Thunderstorms rage within 6 miles of the lair. Creatures within 1 mile feel a sense of dread."
                </small>
            </div>
        </div>

        <!-- BACK IMAGE -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Card Back Image</h5>
            </div>
            <div class="card-body">
                <input type="file" class="form-control" id="image_back" name="image_back" accept="image/*">
                <small class="text-muted">
                    Recommended: Landscape-oriented image showing the lair environment. Will be displayed on the back of the horizontal card.
                </small>
            </div>
        </div>

        <button type="submit" class="btn btn-success btn-lg">
            <i class="fa-solid fa-save"></i> Create Lair Card
        </button>
        <a href="index.php?url=my-lair-cards" class="btn btn-secondary btn-lg">Cancel</a>
    </form>
</main>

<script>
function addLairAction() {
    const container = document.getElementById('lair-actions-container');
    const entry = document.createElement('div');
    entry.className = 'lair-action-entry mb-3';
    entry.innerHTML = `
        <div class="row">
            <div class="col-md-4">
                <input type="text" class="form-control" name="lair_action_name[]" 
                       placeholder="Action Name" required>
            </div>
            <div class="col-md-8">
                <div class="input-group">
                    <textarea class="form-control" name="lair_action_description[]" 
                              rows="2" placeholder="Action description..." required></textarea>
                    <button type="button" class="btn btn-outline-danger" onclick="this.closest('.lair-action-entry').remove()">
                        <i class="fa-solid fa-trash"></i>
                    </button>
                </div>
            </div>
        </div>
    `;
    container.appendChild(entry);
}
</script>

<?php require_once ROOT . '/src/views/templates/footer.php'; ?>
