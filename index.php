<?php
session_start();
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/anti_inspect.php';
require_once __DIR__ . '/includes/functions.php';

if (isset($_SESSION['student'])) {
    header('Location: student/student-dashboard');
    exit;
} elseif (isset($_SESSION['admin'])) {
    header('Location: admin');
    exit;
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sri Krishna Academy</title>
    <link rel="icon" type="image/x-icon" href="assets/images/ska-logo.png">

    <!-- Bootstrap 5.3 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Icons & Fonts -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Pacifico&display=swap"
        rel="stylesheet">

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #ffffff 0%, #fee2e2 50%, #dc2626 100%);
            min-height: 100vh;
            overflow-x: hidden;
        }

        /* ================= SIMPLE NAVBAR ================= */
        .main-navbar {
            background-color: #dc2626;
        }

        .main-navbar .navbar-brand span {
            font-weight: 600;
            letter-spacing: 0.03em;
        }

        /* ================= SLOW LOADER ================= */
        #page-loader {
            position: fixed;
            inset: 0;
            background: radial-gradient(circle at 20% 20%, #dc2626 0%, #7f1d1d 55%, #450a0a 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9999;
            opacity: 1;
            transition: opacity 1.5s cubic-bezier(0.25, 0.46, 0.45, 0.94);
        }

        #page-loader.fade-out {
            opacity: 0;
        }

        .loader-inner {
            text-align: center;
            color: #fef2f2;
        }

        .loader-logo {
            width: 350px;
            height: 350px;
            object-fit: contain;
            border-radius: 24px;
            background: #ffffff;
            padding: 10px;
            box-shadow: 0 20px 60px rgba(127, 29, 29, 0.8);
            animation: logoPop 1s ease-out forwards;
        }

        @keyframes logoPop {
            0% {
                transform: scale(0.6);
                opacity: 0;
            }

            60% {
                transform: scale(1.05);
                opacity: 1;
            }

            100% {
                transform: scale(1);
            }
        }

        #page-content {
            opacity: 0;
        }

        #page-content.content-fade-in {
            animation: contentFadeIn 1s ease-out forwards;
        }

        @keyframes contentFadeIn {
            from {
                opacity: 0;
                transform: translateY(15px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* ================= MAIN HERO ================= */
        .hero-section {
            min-height: calc(100vh - 56px);
            display: flex;
            align-items: center;
            position: relative;
            overflow: hidden;
        }

        .hero-section::before {
            content: '';
            position: absolute;
            inset: 0;
            background:
                radial-gradient(circle at 20% 80%, rgba(220, 38, 38, 0.25) 0%, transparent 55%),
                radial-gradient(circle at 80% 20%, rgba(254, 226, 226, 0.35) 0%, transparent 55%);
            animation: heroFloat 20s ease-in-out infinite;
            z-index: 1;
        }

        @keyframes heroFloat {

            0%,
            100% {
                transform: scale(1) translateY(0);
            }

            50% {
                transform: scale(1.05) translateY(-20px);
            }
        }

        .hero-content {
            position: relative;
            z-index: 2;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        .logo-institute {
            display: inline-flex;
            align-items: center;
            gap: 18px;
            margin-bottom: 28px;
            background: rgba(127, 29, 29, 0.35);
            backdrop-filter: blur(14px);
            padding: 12px 26px;
            border-radius: 999px;
            box-shadow: 0 14px 45px rgba(127, 29, 29, 0.7);
        }

        .institute-logo {
            width: 82px;
            height: 82px;
            border-radius: 18px;
            object-fit: contain;
            background: #ffffff;
            padding: 6px;
        }

        .institute-name {
            font-family: 'Georgia';
            font-size: 2.4rem;
            letter-spacing: 0.08em;
            color: #ffffff;
            text-shadow:
                0 0 6px rgba(127, 29, 29, 0.9),
                0 0 18px rgba(220, 38, 38, 0.9),
                0 0 32px rgba(239, 68, 68, 0.95);
        }

        .institute-address {
            font-family: 'Poppins', sans-serif;
            font-size: 1rem;
            letter-spacing: 0.05em;
            color: #ffffff;
            text-align: left;
            margin-top: 4px;
            font-weight: 400;
        }

        .hero-tagline {
            font-size: 4.2rem;
            font-weight: 700;
            line-height: 1.1;
            background: linear-gradient(135deg, #008B8B 0%, #fef2f2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 24px;
        }

        .hero-subtitle {
            font-size: 1.3rem;
            color: rgba(127, 29, 29, 0.92);
            max-width: 650px;
            line-height: 1.6;
            margin-bottom: 48px;
        }

        .cta-buttons {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
            justify-content: flex-start;
        }

        .btn-hero {
            padding: 18px 36px;
            font-size: 1.05rem;
            font-weight: 600;
            border-radius: 50px;
            text-transform: uppercase;
            letter-spacing: 1px;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 12px;
            min-height: 60px;
        }

        .btn-hero-primary {
            background: linear-gradient(135deg, #dc2626, #991b1b);
            color: #ffffff;
            box-shadow: 0 12px 40px rgba(220, 38, 38, 0.4);
        }

        .btn-hero-primary:hover {
            transform: translateY(-4px);
            box-shadow: 0 20px 50px rgba(220, 38, 38, 0.6);
            color: #ffffff;
        }

        .director-photo-wrapper {
            text-align: center;
            margin-top: -36px;
            margin-bottom: 36px;
        }

        .director-photo {
            display: inline-block;
            width: 100%;
            max-width: 1000px;
            height: auto;
            border-radius: 18px;
            box-shadow: 0 18px 45px rgba(127, 29, 29, 0.35);
            object-fit: cover;
        }

        /* ================= ABOUT + DIRECTOR ================= */
        .about-section {
            padding: 100px 0 80px;
            background: rgba(255, 255, 255, 0.97);
        }

        .section-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        .section-title {
            font-size: 3.2rem;
            font-weight: 700;
            text-align: center;
            background: linear-gradient(135deg, #7f1d1d, #991b1b);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 16px;
            position: relative;
        }

        .section-title::after {
            content: '';
            position: absolute;
            bottom: -8px;
            left: 50%;
            transform: translateX(-50%);
            width: 80px;
            height: 4px;
            background: linear-gradient(90deg, #dc2626, #991b1b);
            border-radius: 2px;
        }

        .section-subtitle {
            text-align: center;
            font-size: 1.1rem;
            color: #008B8B;
            max-width: 760px;
            margin: 0 auto 56px;
        }

        .about-grid {
            display: grid;
            grid-template-columns: minmax(0, 1.4fr) minmax(0, 1.1fr);
            gap: 60px;
            align-items: flex-start;
        }

        .about-content h3 {
            font-size: 2rem;
            font-weight: 600;
            color: #7f1d1d;
            margin-bottom: 18px;
        }

        .about-content p {
            font-size: 1.05rem;
            color: #008B8B;
            line-height: 1.8;
            margin-bottom: 18px;
        }

        .course-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin: 10px 0 26px;
        }

        .course-tag {
            padding: 6px 14px;
            border-radius: 999px;
            font-size: 0.9rem;
            background: #fef2f2;
            color: #7f1d1d;
            border: 1px solid #fecaca;
            font-weight: 500;
            position: relative;
            cursor: pointer;
            transition: background 0.3s ease, transform 0.2s ease;
        }

        .course-tag:hover {
            background: #fecaca;
            transform: translateY(-2px);
        }

        .course-tag::after {
            content: attr(data-info);
            position: absolute;
            bottom: 130%;
            left: 50%;
            transform: translateX(-50%);
            background: rgba(127, 29, 29, 0.96);
            color: #fef2f2;
            padding: 8px 12px;
            border-radius: 8px;
            font-size: 0.78rem;
            line-height: 1.4;
            white-space: normal;
            width: 220px;
            max-width: 260px;
            pointer-events: none;
            opacity: 0;
            transition: opacity 0.25s ease, transform 0.25s ease;
            z-index: 50;
            box-shadow: 0 4px 25px rgba(127, 29, 29, 0.35);
        }

        .course-tag::before {
            content: '';
            position: absolute;
            bottom: 120%;
            left: 50%;
            transform: translateX(-50%);
            border-width: 6px;
            border-style: solid;
            border-color: rgba(127, 29, 29, 0.96) transparent transparent transparent;
            opacity: 0;
            transition: opacity 0.25s ease;
            z-index: 51;
        }

        .course-tag:hover::after,
        .course-tag:hover::before {
            opacity: 1;
            transform: translateX(-50%) translateY(-4px);
        }

        .about-features {
            display: grid;
            gap: 18px;
        }

        .feature-item {
            display: flex;
            align-items: flex-start;
            gap: 14px;
            padding: 16px 18px;
            background: linear-gradient(135deg, #fef2f2, #fee2e2);
            border-radius: 16px;
            border-left: 4px solid #dc2626;
            transition: all 0.3s ease;
        }

        .feature-item:hover {
            transform: translateY(-3px);
            box-shadow: 0 16px 36px rgba(127, 29, 29, 0.12);
        }

        .feature-icon {
            font-size: 1.6rem;
            color: #dc2626;
            flex-shrink: 0;
            margin-top: 2px;
        }

        .director-card {
            background: linear-gradient(145deg, #7f1d1d, #450a0a);
            color: #fef2f2;
            border-radius: 24px;
            padding: 24px 24px 22px;
            box-shadow: 0 30px 80px rgba(127, 29, 29, 0.45);
            position: relative;
            overflow: hidden;
        }

        .director-card::before {
            content: '"';
            position: absolute;
            top: -40px;
            left: -5px;
            font-size: 120px;
            color: rgba(220, 38, 38, 0.12);
            font-family: 'Georgia', serif;
        }

        .director-header {
            display: flex;
            align-items: center;
            gap: 14px;
            margin-bottom: 14px;
        }

        .director-avatar {
            width: 56px;
            height: 56px;
            border-radius: 50%;
            background: radial-gradient(circle, #dc2626, #7f1d1d);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: #ffffff;
            font-weight: 600;
            border: 2px solid rgba(220, 38, 38, 0.7);
        }

        .director-name {
            font-weight: 600;
            font-size: 1.1rem;
            color: #ffffff;
        }

        .director-role {
            font-size: 0.9rem;
            color: #fecaca;
        }

        .director-message {
            font-size: 0.98rem;
            color: #fbf9f9ff;
            line-height: 1.8;
            margin-bottom: 10px;
        }

        .director-footer {
            font-size: 0.85rem;
            color: #fecaca;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 6px;
        }

        /* ================= AFFILIATES ================= */
        .affiliate-card {
            min-height: 120px;
            transition: transform 0.25s ease, box-shadow 0.25s ease;
            background: #ffffff;
            border-radius: 18px;
            box-shadow: 0 10px 30px rgba(127, 29, 29, 0.12);
        }

        .about-section--compact {
            padding: 16px 0 40px;
        }

        .affiliate-card:hover {
            transform: translateY(-4px) scale(1.02);
            box-shadow: 0 18px 40px rgba(127, 29, 29, 0.18);
        }

        .affiliate-logo {
            max-height: 80px;
            object-fit: contain;
        }

        @media (max-width: 992px) {
            .about-grid {
                grid-template-columns: 1fr;
                gap: 40px;
            }

            .cta-buttons {
                flex-direction: column;
                align-items: flex-start;
            }

            .btn-hero {
                width: 100%;
                max-width: 320px;
                justify-content: center;
            }
        }

        @media (max-width: 768px) {
            .hero-tagline {
                font-size: 3rem;
            }

            .section-title {
                font-size: 2.4rem;
            }

            .about-content h3 {
                font-size: 1.7rem;
            }
        }

        .banner-slider {
            position: relative;
            width: 100%;
            max-width: 1000px;
            margin: 0 auto;
            aspect-ratio: 16 / 9;
            overflow: hidden;
        }

        .banner-slide {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            opacity: 0;
            animation: bannerRotate 12s infinite;
        }

        .banner-slide:nth-child(1) {
            position: relative;
            animation-delay: 0s;
        }

        .banner-slide:nth-child(2) {
            animation-delay: 4s;
        }

        .banner-slide:nth-child(3) {
            animation-delay: 8s;
        }

        @keyframes bannerRotate {
            0% {
                opacity: 0;
            }

            3% {
                opacity: 1;
            }

            33% {
                opacity: 1;
            }

            36% {
                opacity: 0;
            }

            100% {
                opacity: 0;
            }
        }
    </style>
</head>

<body>
    <!-- TOP NAVBAR WITH LOGIN DROPDOWN -->
    <nav class="navbar navbar-expand-lg navbar-dark main-navbar">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="#">
                <img src="assets/images/ska-logo.png" alt="Logo" width="36" height="36" class="me-2 rounded">
                <span>Sri Krishna Academy</span>
            </a>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavbar"
                aria-controls="mainNavbar" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse justify-content-end" id="mainNavbar">
                <ul class="navbar-nav mb-2 mb-lg-0 align-items-lg-center">
                    <li class="nav-item">
                        <a class="nav-link" href="#about">About</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#gallery">Gallery</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#courses">Courses</a>
                    </li>
                    <!-- USER ACCESS DROPDOWN -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="userAccessDropdown" role="button"
                            data-bs-toggle="dropdown" aria-expanded="false">
                            User Access
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userAccessDropdown">
                            <li>
                                <a class="dropdown-item d-flex align-items-center" href="student-login">
                                    <i class="bi bi-box-arrow-in-right me-2"></i>Student Login
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item d-flex align-items-center" href="admin/login">
                                    <i class="bi bi-person-gear me-2"></i>Admin Login
                                </a>
                            </li>
                            <li>
                                <hr class="dropdown-divider">
                            </li>
                            <li>
                                <a class="dropdown-item d-flex align-items-center" href="verify-cert">
                                    <i class="bi bi-patch-check me-2"></i>Verify Certificate
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item d-flex align-items-center" href="verify-marksheet">
                                    <i class="bi bi-file-earmark-check me-2"></i>Verify Marksheet
                                </a>
                            </li>
                        </ul>
                    </li>
                    <li class="nav-item ms-lg-3 mt-2 mt-lg-0">
                        <a class="btn btn-danger btn-sm px-3" href="student-registration-form">
                            <i class="bi bi-pencil-square me-1"></i>New Registration
                        </a>
                    </li>
                    <li class="nav-item ms-lg-3 mt-2 mt-lg-0">
                        <div id="google_translate_element"></div>
                    </li>

                </ul>
            </div>
        </div>
    </nav>

    <!-- SLOW ANIMATED LOADER -->
    <div id="page-loader">
        <div class="loader-inner">
            <img src="assets/images/ska-logo.png" class="loader-logo" alt="Sri Krishna Academy Logo">
        </div>
    </div>

    <!-- MAIN CONTENT -->
    <div id="page-content">
        <!-- Hero Section -->
        <section class="hero-section">
            <div class="hero-content">
                <p
                    style="text-align: left; margin-bottom: 16px; font-family: Georgia, serif; font-size: 2.1rem; color: #dc2626;">
                    Est. 1992 | U-DISE: 10060800613 | Reg No: PSS-18/2013
                </p>
                <div class="logo-institute">
                    <img src="assets/images/ska-logo.png" alt="School Logo" class="institute-logo">
                    <div>
                        <div class="institute-name">Sri Krishna Academy</div>
                        <div class="institute-address"><i class="bi bi-geo-alt-fill me-1"></i>Karjain Bazar, Supaul -
                            852215</div>

                    </div>
                </div>

                <h1 class="hero-tagline">
                    Empowering Students Through
                    <span
                        style="background: linear-gradient(135deg, #dc2626, #991b1b);-webkit-background-clip: text;-webkit-text-fill-color: transparent;">
                        Quality Education
                    </span>
                </h1>

                <p class="hero-subtitle">
                    Committed to nurturing young minds with excellence in academics, character development,
                    and holistic growth – preparing students for a bright and successful future.
                </p>

                <div class="cta-buttons">
                    <a href="student-registration-form" class="btn-hero btn-hero-primary">
                        <i class="bi bi-pencil-square"></i> New Student Admission
                    </a>
                    <a href="enquiry" class="btn-hero btn-hero-primary">
                        <i class="bi bi-chat-dots-fill"></i> Enquiry / Contact Us
                    </a>
                </div>
            </div>
        </section>

        <!-- About + Director Section -->
        <section class="about-section" id="about">
            <div class="section-container">

                <div class="director-photo-wrapper">
                    <div class="banner-slider">
                        <img src="assets/banner/ska-banner-ai.jpg" alt="Sri Krishna Academy Banner 1"
                            class="director-photo banner-slide">
                        <img src="assets/banner/ska-kids.jpg" alt="Sri Krishna Academy Banner 2"
                            class="director-photo banner-slide">
                        <img src="assets/banner/ska-banner.jpg" alt="Sri Krishna Academy Banner 3"
                            class="director-photo banner-slide">
                    </div>
                </div>

                <h2 class="section-title">About Sri Krishna Academy</h2>
                <p class="section-subtitle">
                    A government-registered school and coaching institute in Bihar, committed to holistic education and
                    competitive exam preparation. We nurture young minds through quality academics while specializing in
                    entrance coaching for Navodaya Vidyalaya, Sainik School, Netarhat Vidyalaya and other prestigious
                    institutions.
                </p>

                <div class="about-grid">
                    <div class="about-content">
                        <h3>Quality Education & Excellence in Entrance Exam Preparation</h3>
                        <p>
                            Sri Krishna Academy combines the best of both worlds – comprehensive school education
                            following government curriculum and specialized coaching for competitive entrance
                            examinations. Our dual approach ensures students receive strong academic foundation while
                            preparing for premier residential schools across India.
                        </p>
                        <p>
                            Our school curriculum focuses on holistic development with emphasis on academics,
                            co-curricular activities and character building. Simultaneously, our coaching division
                            provides intensive training in Mental Ability, Arithmetic, Language Skills, General
                            Knowledge and exam-specific strategies for Navodaya, Sainik School, Netarhat and similar
                            entrance tests.
                        </p>

                        <div class="course-tags">
                            <span class="course-tag"
                                data-info="Government-registered school education with comprehensive curriculum and holistic development.">
                                School Education (Govt. Registered)
                            </span>
                            <span class="course-tag"
                                data-info="Navodaya Vidyalaya entrance coaching for Class 6 admission across India.">
                                Navodaya Vidyalaya Entrance Exam
                            </span>
                            <span class="course-tag"
                                data-info="Sainik School coaching includes Math, English, Intelligence and GK preparation.">
                                Sainik School Entrance Preparation
                            </span>
                            <span class="course-tag"
                                data-info="Netarhat Vidyalaya coaching for Bihar's premier residential school admission.">
                                Netarhat Vidyalaya Entrance
                            </span>
                            <span class="course-tag"
                                data-info="Mock tests, practice papers and regular assessments for exam readiness.">
                                Mock Tests &amp; Regular Assessments
                            </span>
                        </div>

                        <div class="about-features">
                            <div class="feature-item">
                                <div class="feature-icon">💻</div>
                                <div>
                                    <strong>Dual Focus: School + Coaching</strong><br>
                                    Complete academic education along with specialized entrance exam preparation under
                                    one roof for comprehensive student development.
                                </div>
                            </div>
                            <div class="feature-item">
                                <div class="feature-icon">📊</div>
                                <div>
                                    <strong>Government-Registered Institution</strong><br>
                                    Official recognition ensures quality standards, proper curriculum and certified
                                    education for your child's secure future.
                                </div>
                            </div>
                            <div class="feature-item">
                                <div class="feature-icon">🎓</div>
                                <div>
                                    <strong>Experienced Faculty &amp; Mentorship</strong><br>
                                    Dedicated teachers provide personalized attention, regular assessments and
                                    continuous guidance for both academics and competitive exams.
                                </div>
                            </div>
                        </div>
                    </div>

                    <div>
                        <div class="director-card">
                            <div class="director-header">
                                <div class="director-avatar">
                                    <i class="bi bi-person-badge"></i>
                                </div>
                                <div>
                                    <div class="director-name">Director's Message</div>
                                    <div class="director-role">Sri Krishna Academy</div>
                                </div>
                            </div>
                            <p class="director-message"
                                style="font-style: italic; color: #fca5a5; margin-bottom: 12px;">
                                "Education is the manifestation of perfection already in man." – Swami Vivekananda
                            </p>
                            <p class="director-message">
                                "At Sri Krishna Academy, we believe every child deserves both excellent education and
                                the opportunity to reach their highest potential. As a government-registered
                                institution, we are committed to providing quality schooling and specialized entrance
                                coaching that transforms dreams into achievements."
                            </p>
                            <p class="director-message"
                                style="font-style: italic; color: #fca5a5; margin-bottom: 12px;">
                                "अमृतं तु विद्या – Knowledge is immortal nectar that enriches life forever."
                            </p>
                            <p class="director-message">
                                "Our integrated approach of school education and competitive exam preparation ensures
                                students build strong foundations while staying focused on their aspirations for premier
                                institutions like Navodaya, Sainik School and Netarhat."
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </section>


        <!-- OUR AFFILIATES SECTION -->
        <section class="about-section about-section--compact">
            <div class="section-container">
                <h2 class="section-title">Our Affiliates</h2>
                <p class="section-subtitle">
                    Recognized and associated with trusted organizations that enhance the value of our certifications.
                </p>

                <div class="row justify-content-center g-4">
                    <div class="col-6 col-md-4 col-lg-3">
                        <div class="affiliate-card d-flex align-items-center justify-content-center p-3">
                            <img src="assets/banner/msme.jpg" alt="MSME" class="img-fluid affiliate-logo">
                        </div>
                    </div>

                    <div class="col-6 col-md-4 col-lg-3">
                        <div class="affiliate-card d-flex align-items-center justify-content-center p-3">
                            <img src="assets/banner/iso.png" alt="ISO 9001:2015" class="img-fluid affiliate-logo">
                        </div>
                    </div>

                    <div class="col-6 col-md-4 col-lg-3">
                        <div class="affiliate-card d-flex align-items-center justify-content-center p-3">
                            <img src="assets/banner/digi.png" alt="Digital Affiliate" class="img-fluid affiliate-logo">
                        </div>
                    </div>
                </div>
            </div>
        </section>


        <!-- Gallery iframe -->
        <section class="about-section about-section--compact">
            <div class="section-container" id="gallery">
                <h2 class="section-title">Photo Gallery</h2>
                <p class="section-subtitle">
                    Explore moments captured at Sri Krishna Academy.
                </p>

                <div class="ratio ratio-16x9">
                    <iframe src="gallery.php" title="Sri Krishna Academy Gallery" loading="lazy"
                        style="border: 0; border-radius: 16px; box-shadow: 0 18px 40px rgba(127,29,29,0.35);">
                    </iframe>
                </div>
            </div>
        </section>


        <!-- Entrance Exams & Competitive Tests Section -->
        <section class="about-section about-section--compact" id="courses">
            <div class="section-container">
                <h2 class="section-title">Entrance Exams &amp; Competitive Tests</h2>
                <p class="section-subtitle">
                    Move your mouse over each item to see a short description of the examination and eligibility.
                </p>

                <div class="about-grid">
                    <div class="about-content">
                        <h3>Co-Educational Residential School Exams</h3>
                        <div class="course-tags">
                            <span class="course-tag"
                                data-info="Navodaya Vidyalaya Selection Test for Class 6 admission in co-educational residential JNVs with hostel facilities across India.">
                                JNVST (Navodaya Entrance)
                            </span>
                            <span class="course-tag"
                                data-info="Netarhat Vidyalaya entrance for Class 6 admission in Bihar's premier co-educational residential school with hostel.">
                                Netarhat Vidyalaya Entrance
                            </span>
                            <span class="course-tag"
                                data-info="Kendriya Vidyalaya admission test for co-educational central schools, some with hostel facilities.">
                                KVS Admission Test
                            </span>
                            <span class="course-tag"
                                data-info="All India Sainik School Entrance Exam for boys for Class 6 and 9 admission in residential military schools.">
                                AISSEE (Sainik School - Boys)
                            </span>
                            <span class="course-tag"
                                data-info="Rashtriya Military School entrance for boys for Class 6 and 9 in residential military institutions.">
                                RMS Entrance (Boys)
                            </span>
                            <span class="course-tag"
                                data-info="Rashtriya Indian Military College for boys seeking career in armed forces with residential facilities.">
                                RIMC (Boys Residential)
                            </span>
                        </div>
                    </div>

                    <div class="about-content">
                        <h3>Scholarship & Olympiad Exams (Co-Ed)</h3>
                        <div class="course-tags">
                            <span class="course-tag"
                                data-info="National Talent Search Examination for Class 10 co-educational students to identify talented students nationwide.">
                                NTSE (National Talent Search)
                            </span>
                            <span class="course-tag"
                                data-info="National Merit-cum-Means Scholarship for economically weaker meritorious students in co-educational schools.">
                                NMMS (Merit Scholarship)
                            </span>
                            <span class="course-tag"
                                data-info="Bihar State-level scholarship exams for meritorious boys and girls studying in co-educational schools.">
                                Bihar Scholarship Exams
                            </span>
                            <span class="course-tag"
                                data-info="National Science Olympiad for co-educational school students to test scientific aptitude and reasoning.">
                                NSO (Science Olympiad)
                            </span>
                            <span class="course-tag"
                                data-info="International Mathematics Olympiad for boys and girls with strong mathematical skills.">
                                IMO (Math Olympiad)
                            </span>
                            <span class="course-tag"
                                data-info="National Cyber Olympiad for testing IT and computer skills of co-educational school students.">
                                NCO (Cyber Olympiad)
                            </span>
                            <span class="course-tag"
                                data-info="International English Olympiad for testing language proficiency in co-educational environment.">
                                IEO (English Olympiad)
                            </span>
                            <span class="course-tag"
                                data-info="National Level Science Talent Search Exam for co-ed students across India.">
                                NLSTSE
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </section>

    </div>

    <!-- Bootstrap 5.3 JS bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        window.addEventListener('load', function () {
            const loader = document.getElementById('page-loader');
            const content = document.getElementById('page-content');

            setTimeout(function () {
                loader.classList.add('fade-out');

                setTimeout(function () {
                    loader.style.display = 'none';
                    content.classList.add('content-fade-in');
                }, 100);
            }, 500);
        });
    </script>
    <script type="text/javascript">
        function googleTranslateElementInit() {
            new google.translate.TranslateElement(
                {
                    pageLanguage: 'en',          // your main language
                    includedLanguages: 'en,hi',  // only English and Hindi
                    layout: google.translate.TranslateElement.InlineLayout.SIMPLE
                },
                'google_translate_element'
            );
        }
    </script>
    <script type="text/javascript" src="//translate.google.com/translate_a/element.js?cb=googleTranslateElementInit">
    </script>

    <?php include 'includes/footer.php'; ?>
</body>

</html>