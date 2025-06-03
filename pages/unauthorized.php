<?php
require_once '../includes/header.php';
?>

<div class="container">
    <div class="row justify-content-center mt-5">
        <div class="col-md-6">
            <div class="card">
                <div class="card-body text-center">
                    <h1 class="display-1 text-danger">
                        <i class="fas fa-exclamation-triangle"></i>
                    </h1>
                    <h2 class="card-title">Acceso No Autorizado</h2>
                    <p class="card-text">Lo sentimos, no tienes permiso para acceder a esta sección.</p>
                    <a href="/sistema-ventas/pages/dashboard.php" class="btn btn-primary">
                        <i class="fas fa-home"></i> Volver al Inicio
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
