<nav class="navbar navbar-expand-lg navbar-dark">
    <?php require_once __DIR__ . '/../includes/anti_inspect.php'; ?>

    <div class="container-fluid">
        <!-- Beautiful Logo/Brand -->
        <a class="navbar-brand fw-bold" href="index">
            <i class="bi bi-laptop-fill me-2"></i>SKA Admin
        </a>

        <!-- Toggler -->
        <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#nav">
            <span class="navbar-toggler-icon"></span>
        </button>

        <!-- Collapsible Menu -->
        <div class="collapse navbar-collapse" id="nav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link active-nav" href="index">
                        <i class="bi bi-house-door-fill me-1"></i>Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="students">
                        <i class="bi bi-people-fill me-1"></i>Students
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="add-student">
                        <i class="bi bi-person-plus-fill me-1"></i>Add Student
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="bulk-generation">
                        <i class="bi bi-gear-fill me-1"></i>Bulk Generation Hub
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="courses">
                        <i class="bi bi-book-half me-1"></i>Courses
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="add-bulk-result">
                        <i class="bi bi-clipboard-check me-1"></i>Results
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="add-exam">
                        <i class="bi bi-alarm me-1"></i>Schedule Exam
                    </a>
                </li>
            </ul>

            <!-- Logout Button -->
            <a href="logout" class="btn btn-gradient-logout ms-3">
                <i class="bi bi-box-arrow-right me-1"></i>Logout
            </a>
        </div>
    </div>
</nav>

<style>
    /* Beautiful Modern Navbar Styles */
    .navbar {
        background: linear-gradient(135deg, #1e293b 0%, #334155 50%, #0f172a 100%);
        backdrop-filter: blur(20px);
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
        padding: 1rem 0;
        position: relative;
        overflow: hidden;
    }

    .navbar::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.1), transparent);
        animation: navbar-shine 3s infinite;
    }

    @keyframes navbar-shine {
        0% {
            left: -100%;
        }

        100% {
            left: 100%;
        }
    }

    .navbar-brand {
        font-size: 1.6rem;
        font-weight: 800;
        background: linear-gradient(135deg, #10b981, #059669, #22c55e);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        letter-spacing: -0.02em;
        padding: 0.5rem 1rem;
        border-radius: 12px;
        transition: all 0.3s ease;
        white-space: nowrap;
    }

    .navbar-brand:hover {
        transform: scale(1.05);
        background: linear-gradient(135deg, #22c55e, #10b981, #059669);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
    }

    /* Nav Links */
    .nav-link {
        color: rgba(255, 255, 255, 0.85) !important;
        font-weight: 500;
        padding: 0.75rem 1.5rem !important;
        border-radius: 12px;
        margin: 0 0.25rem;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        position: relative;
        overflow: hidden;
        white-space: nowrap;
        display: inline-flex;
        align-items: center;
    }

    .nav-link i {
        display: inline-block;
        vertical-align: middle;
    }

    .nav-link::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(16, 185, 129, 0.2), transparent);
        transition: left 0.5s;
    }

    .nav-link:hover::before {
        left: 100%;
    }

    .nav-link:hover {
        color: #ffffff !important;
        background: rgba(16, 185, 129, 0.15);
        backdrop-filter: blur(10px);
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(16, 185, 129, 0.3);
    }

    .active-nav {
        color: #10b981 !important;
        background: rgba(16, 185, 129, 0.2);
        box-shadow: inset 0 0 0 2px rgba(16, 185, 129, 0.4);
    }

    /* Logout Button */
    .btn-gradient-logout {
        background: linear-gradient(135deg, #ef4444, #dc2626) !important;
        border: none !important;
        color: white !important;
        font-weight: 600;
        padding: 0.75rem 1.75rem !important;
        border-radius: 50px !important;
        transition: all 0.3s ease !important;
        box-shadow: 0 4px 20px rgba(239, 68, 68, 0.4);
        white-space: nowrap;
    }

    .btn-gradient-logout:hover {
        transform: translateY(-3px) scale(1.05) !important;
        box-shadow: 0 12px 35px rgba(239, 68, 68, 0.6) !important;
        color: white !important;
    }

    /* Mobile Responsiveness */
    @media (max-width: 991px) {
        .navbar-collapse {
            background: rgba(30, 41, 59, 0.98);
            backdrop-filter: blur(20px);
            margin-top: 1rem;
            border-radius: 16px;
            padding: 1.5rem;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.4);
        }

        .nav-link {
            margin: 0.5rem 0 !important;
        }
    }
</style>
