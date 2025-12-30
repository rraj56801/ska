<?php
// footer.php
?>
<footer class="mt-5">
    <style>
        .ska-footer {
            background: linear-gradient(135deg, #1f2937, #111827);
            color: #e5e7eb;
            padding: 40px 0 20px;
            position: relative;
            overflow: hidden;
        }

        .ska-footer::before {
            content: '';
            position: absolute;
            inset: 0;
            background:
                radial-gradient(circle at 10% 0%, rgba(220, 38, 38, 0.08) 0, transparent 50%),
                radial-gradient(circle at 90% 100%, rgba(185, 28, 28, 0.06) 0, transparent 55%);
            opacity: 0.7;
            pointer-events: none;
        }

        .ska-footer-inner {
            position: relative;
            z-index: 1;
        }

        .ska-footer-title {
            font-family: 'Pacifico', cursive;
            font-size: 1.6rem;
            letter-spacing: 0.08em;
            color: #f3f4f6;
        }

        .ska-footer-subtitle {
            font-size: 0.95rem;
            color: #d1d5db;
            line-height: 1.6;
        }

        .ska-footer-separator {
            border-top: 1px solid rgba(156, 163, 175, 0.2);
            margin: 18px 0 12px;
        }

        .ska-footer-copy {
            font-size: 0.85rem;
            color: #d1d5db;
        }

        .ska-footer-copy span {
            color: #f9fafb;
            font-weight: 500;
        }

        /* Stats Section */
        .ska-stats {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 1.5rem;
            padding: 1.5rem 0;
            margin-bottom: 1.5rem;
            border-bottom: 1px solid rgba(156, 163, 175, 0.15);
        }

        .about_point {
            text-align: center;
            flex: 1 1 120px;
            min-width: 100px;
        }

        .about_point i {
            font-size: 2rem;
            color: #f87171;
            margin-bottom: 0.5rem;
        }

        .about_point .counter-value {
            font-size: 1.75rem;
            font-weight: 700;
            color: #f9fafb;
            margin: 0.25rem 0 0.25rem;
        }

        .about_point h3 {
            font-size: 0.95rem;
            font-weight: 500;
            color: #d1d5db;
            margin: 0;
            line-height: 1.3;
        }

        @media (max-width: 768px) {
            .ska-stats {
                gap: 1rem;
                padding: 1rem 0;
            }

            .about_point .counter-value {
                font-size: 1.5rem;
            }

            .about_point h3 {
                font-size: 0.9rem;
            }
        }

        @media (max-width: 576px) {
            .ska-footer-copy-wrapper {
                flex-direction: column;
                align-items: flex-start !important;
                gap: 8px;
            }
        }
    </style>

    <script>
        document.addEventListener("DOMContentLoaded", function () {
            const counters = document.querySelectorAll(".counter-value");
            const statsSection = document.querySelector(".ska-stats");

            const animateCounter = (counter) => {
                const target = parseInt(counter.getAttribute("data-count"));
                const duration = 2000; // ms
                const step = target / (duration / 16);
                let current = 0;

                // Clear any existing interval
                if (counter.intervalId) clearInterval(counter.intervalId);

                counter.intervalId = setInterval(() => {
                    current += step;
                    if (current >= target) {
                        counter.textContent = target.toLocaleString();
                        clearInterval(counter.intervalId);
                    } else {
                        counter.textContent = Math.floor(current).toLocaleString();
                    }
                }, 16);
            };

            const observer = new IntersectionObserver(
                (entries) => {
                    entries.forEach(entry => {
                        if (entry.isIntersecting) {
                            // Reset all counters to 0 before animating
                            counters.forEach(counter => {
                                counter.textContent = "0";
                            });
                            // Animate each counter
                            counters.forEach(animateCounter);
                        }
                    });
                },
                { threshold: 0.1 }
            );

            observer.observe(statsSection);
        });
    </script>

    <div class="ska-footer">
        <div class="container ska-footer-inner">
            <div class="row">
                <div class="col-md-6 mb-3 mb-md-0">
                    <div class="ska-footer-title">
                        Sri Krishna Academy
                    </div>
                    <div class="ska-footer-subtitle">
                        Government-registered school and coaching institute specializing in entrance exam preparation
                        for Navodaya, Sainik School, Netarhat and other competitive exams.
                    </div>
                </div>
                <div class="col-md-6 d-flex flex-column justify-content-center align-items-md-end">
                    <div class="ska-footer-subtitle">
                        <i class="bi bi-envelope-fill me-2"></i>Email: ska@gmail.com
                    </div>
                    <div class="ska-footer-subtitle">
                        <i class="bi bi-telephone-fill me-2"></i>Phone: +91-9430522843
                    </div>
                    <div class="ska-footer-subtitle">
                        <i class="bi bi-whatsapp me-2"></i>WhatsApp: +91-8405913144
                    </div>
                </div>
            </div>

            <!-- Stats Section -->
            <div class="ska-stats">
                <div class="about_point counter">
                    <i class="bi bi-award-fill icon_conunt"></i>
                    <p class="counter-value" data-count="433">0</p>
                    <h3>Students<br>Enrolled</h3>
                </div>
                <div class="about_point counter">
                    <i class="bi bi-trophy-fill icon_conunt"></i>
                    <p class="counter-value" data-count="89">0</p>
                    <h3>Selection<br>Success Rate %</h3>
                </div>
                <div class="about_point counter">
                    <i class="bi bi-book-fill icon_conunt"></i>
                    <p class="counter-value" data-count="15">0</p>
                    <h3>Entrance<br>Exams Covered</h3>
                </div>
                <div class="about_point counter">
                    <i class="bi bi-mortarboard-fill icon_conunt"></i>
                    <p class="counter-value" data-count="145">0</p>
                    <h3>Qualified<br>Students</h3>
                </div>
                <div class="about_point counter">
                    <i class="bi bi-people-fill icon_conunt"></i>
                    <p class="counter-value" data-count="12">0</p>
                    <h3>Experienced<br>Teachers</h3>
                </div>
                <div class="about_point counter">
                    <i class="bi bi-calendar-check-fill icon_conunt"></i>
                    <p class="counter-value" data-count="33">0</p>
                    <h3>Years of<br>Excellence</h3>
                </div>
            </div>

            <div class="ska-footer-separator"></div>

            <div class="d-flex justify-content-between align-items-center ska-footer-copy-wrapper">
                <div class="ska-footer-copy">
                    &copy; <?php echo date('Y'); ?> <span>Sri Krishna Academy</span>. All rights reserved. | Est. 1992 |
                    U-DISE: 10060800613
                </div>
                <div class="ska-footer-copy">
                    Designed &amp; Developed with <span>&hearts;</span> by
                    <a href="https://www.linkedin.com/in/rr56801/" target="_blank" rel="noopener noreferrer"
                        style="color: #ffffff; text-decoration: none; font-weight: 700; transition: all 0.3s ease;"
                        onmouseover="this.style.color='#fca5a5'; this.style.textDecoration='underline';"
                        onmouseout="this.style.color='#ffffff'; this.style.textDecoration='none';">
                        Rahul
                    </a>
                    <a href="https://www.linkedin.com/in/rr56801/" target="_blank" rel="noopener noreferrer"
                        title="Connect on LinkedIn"
                        style="margin-left: 8px; display: inline-flex; align-items: center; justify-content: center; width: 24px; height: 24px; background: #0077b5; color: white; border-radius: 4px; text-decoration: none; transition: transform 0.2s ease;"
                        onmouseover="this.style.transform='scale(1.1)';" onmouseout="this.style.transform='scale(1)';">
                        <i class="bi bi-linkedin"></i>
                    </a>
                    <a href="https://wa.me/+918405913144?text=Hi%20Rahul%2C%20I%27d%20like%20to%20connect%20with%20you%20for%20your%20school%20website."
                        target="_blank" rel="noopener noreferrer" title="Contact via WhatsApp"
                        style="margin-left: 8px; display: inline-flex; align-items: center; justify-content: center; width: 24px; height: 24px; background: #25D366; color: white; border-radius: 4px; text-decoration: none; transition: transform 0.2s ease;"
                        onmouseover="this.style.transform='scale(1.1)';" onmouseout="this.style.transform='scale(1)';">
                        <i class="bi bi-whatsapp"></i>
                    </a>

                </div>

            </div>
        </div>
    </div>
</footer>