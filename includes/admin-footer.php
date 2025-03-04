</div> <!-- Fin du conteneur principal -->
            
            <footer class="dashboard-footer">
                <div class="container-fluid">
                    <p>&copy; <?= date('Y') ?> - <?= APP_NAME ?> | Administration</p>
                </div>
            </footer>
        </div> <!-- Fin du main-content -->
    </div> <!-- Fin du dashboard-layout -->

    <!-- Bootstrap 5 JS avec Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- jQuery (nécessaire pour DataTables) -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    
    <!-- JavaScript personnalisé -->
    <script src="<?= APP_URL ?>/assets/js/script.js"></script>
    <script src="<?= APP_URL ?>/assets/js/admin.js"></script>
</body>
</html>