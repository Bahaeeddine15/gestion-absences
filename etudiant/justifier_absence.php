<?php
require_once '../includes/header.php';
require_once '../config/db.php';
isStudent(); // Assurez-vous que c'est un étudiant

$etudiant_id = $_SESSION['user_id'];

// Récupérer les modules où l'étudiant a des absences non justifiées
$stmt = $pdo->prepare("
    SELECT DISTINCT m.id_module, m.nom 
    FROM modules m
    JOIN absences a ON m.id_module = a.id_module
    WHERE a.id_etudiant = ? AND a.justifiee = 0
    ORDER BY m.nom
");
$stmt->execute([$etudiant_id]);
$modules = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="form-section" style="max-width:500px;margin:2rem auto;">
    <h2>Déposer un justificatif d'absence</h2>
    
    <?php if (empty($modules)): ?>
    <div class="info-box">
        <p>Vous n'avez aucune absence non justifiée à ce jour.</p>
    </div>
    <?php else: ?>
    <form action="../upload_justificatif.php" method="post" enctype="multipart/form-data">
        <div class="form-group">
            <label for="module_id">Module concerné :</label>
            <select name="module_id" id="module_id" required>
                <option value="">Sélectionnez un module</option>
                <?php foreach ($modules as $module): ?>
                    <option value="<?php echo $module['id_module']; ?>">
                        <?php echo htmlspecialchars($module['nom']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="form-group">
            <label for="date_absence">Date d'absence :</label>
            <select name="date_absence" id="date_absence" required disabled>
                <option value="">Sélectionnez d'abord un module</option>
            </select>
        </div>
        
        <div class="form-group">
            <label for="justificatif">Justificatif (PDF, JPG, PNG) :</label>
            <input type="file" name="justificatif" id="justificatif" accept=".pdf,.jpg,.jpeg,.png" required>
        </div>
        
        <button type="submit" class="btn btn-primary">Envoyer</button>
    </form>
    
    <script>
        // Script pour charger les dates d'absence selon le module sélectionné
        document.getElementById('module_id').addEventListener('change', function() {
            const moduleId = this.value;
            const dateSelect = document.getElementById('date_absence');
            
            // Réinitialiser le select de date
            dateSelect.innerHTML = '<option value="">Chargement des dates...</option>';
            dateSelect.disabled = true;
            
            if (moduleId) {
                // Requête AJAX pour récupérer les dates d'absence
                fetch('../ajax/get_absences_dates.php?module_id=' + moduleId)
                    .then(response => response.json())
                    .then(data => {
                        // Vider et remplir le select avec les dates disponibles
                        dateSelect.innerHTML = '';
                        
                        if (data.length > 0) {
                            data.forEach(item => {
                                const option = document.createElement('option');
                                option.value = item.date;
                                
                                // Formater la date pour l'affichage (YYYY-MM-DD → DD/MM/YYYY)
                                const date = new Date(item.date);
                                const displayDate = date.toLocaleDateString('fr-FR');
                                
                                option.textContent = displayDate;
                                dateSelect.appendChild(option);
                            });
                            
                            // Activer le select
                            dateSelect.disabled = false;
                            
                            // Sélectionner automatiquement la première date
                            if (dateSelect.options.length > 0) {
                                dateSelect.selectedIndex = 0;
                            }
                        } else {
                            dateSelect.innerHTML = '<option value="">Aucune absence non justifiée pour ce module</option>';
                        }
                    })
                    .catch(error => {
                        console.error('Erreur lors de la récupération des dates:', error);
                        dateSelect.innerHTML = '<option value="">Erreur de chargement</option>';
                    });
            } else {
                dateSelect.innerHTML = "<option value=''>Sélectionnez d'abord un module</option>";
            }
        });
    </script>
    <?php endif; ?>
</div>

<?php require_once '../includes/footer.php'; ?>