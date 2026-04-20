<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.0">
<title>Admin Dashboard - Quản lý Cao ốc</title>

<!-- Bootstrap 5.3 CSS -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<!-- Bootstrap Icons -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<!-- DataTables Bootstrap 5 CSS -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<!-- CSS Branding System -->
<link href="<?= BASE_URL ?>assets/css/style.css" rel="stylesheet">
<link href="<?= BASE_URL ?>assets/css/dark-mode.css" rel="stylesheet">

<style>
    /* Admin Layout Base Styles */
    body { 
        background-color: var(--color-background); 
        overflow-x: hidden; 
    }
    .admin-layout { 
        display: flex; 
        min-height: 100vh; 
    }
    .admin-sidebar { 
        width: 280px; 
        z-index: 1045; /* Bootstrap offcanvas z-index */
    }
    .admin-main-wrapper { 
        flex-grow: 1; 
        display: flex; 
        flex-direction: column; 
        min-width: 0; /* Prevent flex children from overflowing */
        transition: all 0.3s ease; 
    }
    .admin-main-content { 
        flex-grow: 1; 
        padding: 1.5rem; 
        max-width: 1440px; 
        margin: 0 auto; 
        width: 100%; 
    }
    
    /* Responsive Adjustments */
    @media (min-width: 992px) {
        .admin-sidebar { 
            position: fixed; 
            top: 0; 
            bottom: 0; 
            left: 0; 
        }
        .admin-main-wrapper { 
            margin-left: 280px; 
        }
    }
    @media (max-width: 375px) {
        .admin-main-content {
            padding: 1rem;
        }
        .admin-topbar {
            padding-left: 1rem !important;
            padding-right: 1rem !important;
        }
    }
    
    /* Sidebar Navigation Tweaks */
    .admin-sidebar__nav .nav-link { 
        padding: 0.8rem 1.25rem; 
        border-radius: 8px; 
        margin: 0.2rem 1rem; 
        transition: background-color 0.2s; 
    }
    .admin-sidebar__nav .nav-link:hover, 
    .admin-sidebar__nav .nav-link.active { 
        background-color: rgba(255, 255, 255, 0.1); 
        color: var(--color-accent) !important; 
    }
    
    /* Topbar toggler */
    .admin-topbar .navbar-toggler:focus { 
        box-shadow: none; 
    }
</style>
