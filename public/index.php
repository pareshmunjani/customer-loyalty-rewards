
<?php

$pageTitle = 'Welcome | Customer Loyalty Rewards';

require_once __DIR__ . '/../app/includes/header.php';

?>

<!-- HERO SECTION -->
<section class="landing-hero">

    <div class="container">

        <div class="row align-items-center g-5">

            <div class="col-lg-7">

                <span class="hero-eyebrow">
                    ★ CUSTOMER LOYALTY PROGRAM
                </span>

                <h1 class="landing-title">
                    Every Purchase.
                    <span>More Rewards.</span>
                </h1>

                <p class="landing-description">
                    Turn your everyday purchases into exciting rewards.
                    Reveal the unique code inside your product package,
                    collect loyalty points and redeem them for rewards.
                </p>

                <div class="hero-actions">

                    <a href="<?= e(url('customer/register.php')) ?>"
                       class="hero-primary-button">
                        Join the Rewards Program <span>→</span>
                    </a>

                    <a href="<?= e(url('customer/login.php')) ?>"
                       class="hero-secondary-button">
                        Customer Login
                    </a>

                </div>

                <div class="hero-note">
                    <span>✓</span>
                    Simple registration
                    <span>✓</span>
                    Secure point collection
                </div>

            </div>

            <div class="col-lg-5">

                <div class="hero-visual">

                    <div class="visual-decoration decoration-one"></div>
                    <div class="visual-decoration decoration-two"></div>

                    <div class="reward-preview-card">

                        <div class="preview-top">
                            <span>YOUR REWARDS</span>
                            <span class="preview-star">★</span>
                        </div>

                        <div class="preview-gift">🎁</div>

                        <h3>Every Point Counts</h3>

                        <p>
                            Earn points with genuine product package codes
                            and unlock exciting rewards.
                        </p>

                        <div class="preview-bottom">
                            <span>BUY</span>
                            <span>•</span>
                            <span>EARN</span>
                            <span>•</span>
                            <span>REDEEM</span>
                        </div>

                    </div>

                    <div class="floating-label label-top">
                        <span>✦</span> Earn Points
                    </div>

                    <div class="floating-label label-bottom">
                        <span>🎉</span> Enjoy Rewards
                    </div>

                </div>

            </div>

        </div>

    </div>

</section>


<!-- HOW IT WORKS -->
<section class="how-section">

    <div class="container">

        <div class="section-heading text-center">

            <span class="section-eyebrow">SIMPLE PROCESS</span>

            <h2>How Does It Work?</h2>

            <p>
                Four simple steps to turn your purchases into rewards.
            </p>

        </div>

        <div class="row g-4">

            <div class="col-md-6 col-lg-3">

                <div class="process-card">

                    <div class="process-number">01</div>
                    <div class="process-icon">🛍️</div>

                    <h4>Buy a Product</h4>

                    <p>
                        Purchase a participating product from your retailer.
                    </p>

                </div>

            </div>

            <div class="col-md-6 col-lg-3">

                <div class="process-card">

                    <div class="process-number">02</div>
                    <div class="process-icon">🔓</div>

                    <h4>Reveal Your Code</h4>

                    <p>
                        Open the package and reveal the scratch-off reward code.
                    </p>

                </div>

            </div>

            <div class="col-md-6 col-lg-3">

                <div class="process-card">

                    <div class="process-number">03</div>
                    <div class="process-icon">⭐</div>

                    <h4>Collect Points</h4>

                    <p>
                        Enter your genuine unused code to earn loyalty points.
                    </p>

                </div>

            </div>

            <div class="col-md-6 col-lg-3">

                <div class="process-card">

                    <div class="process-number">04</div>
                    <div class="process-icon">🎁</div>

                    <h4>Redeem Rewards</h4>

                    <p>
                        Use your available points to redeem exciting rewards.
                    </p>

                </div>

            </div>

        </div>

    </div>

</section>


<!-- BENEFITS SECTION -->
<section class="benefits-section">

    <div class="container">

        <div class="row align-items-center g-5">

            <div class="col-lg-5">

                <span class="section-eyebrow">WHY JOIN US?</span>

                <h2 class="benefits-title">
                    A little purchase.
                    <span>A lot more value.</span>
                </h2>

                <p class="benefits-description">
                    Your purchases can do more than just bring you a product.
                    Participate in our loyalty program and make every eligible
                    purchase more rewarding.
                </p>

                <a href="<?= e(url('customer/register.php')) ?>"
                   class="benefits-link">
                    Get Started Today →
                </a>

            </div>

            <div class="col-lg-7">

                <div class="row g-3">

                    <div class="col-md-6">

                        <div class="benefit-card">

                            <div class="benefit-icon">🔐</div>

                            <h4>Secure Codes</h4>

                            <p>
                                Each genuine reward code can be redeemed only once.
                            </p>

                        </div>

                    </div>

                    <div class="col-md-6">

                        <div class="benefit-card">

                            <div class="benefit-icon">📊</div>

                            <h4>Track Your Points</h4>

                            <p>
                                View your points balance and transaction history
                                from your customer dashboard.
                            </p>

                        </div>

                    </div>

                    <div class="col-md-6">

                        <div class="benefit-card">

                            <div class="benefit-icon">🎁</div>

                            <h4>Choose Rewards</h4>

                            <p>
                                Explore available rewards and redeem them using
                                your collected points.
                            </p>

                        </div>

                    </div>

                    <div class="col-md-6">

                        <div class="benefit-card">

                            <div class="benefit-icon">👤</div>

                            <h4>Your Own Account</h4>

                            <p>
                                Access your personal points, rewards and
                                redemption history.
                            </p>

                        </div>

                    </div>

                </div>

            </div>

        </div>

    </div>

</section>


<!-- REWARDS SECTION -->
<section class="rewards-section">

    <div class="container">

        <div class="rewards-content">

            <div>
                <span class="section-eyebrow">MAKE YOUR POINTS COUNT</span>

                <h2>Ready to unlock your rewards?</h2>

                <p>
                    Create your account, collect points from eligible
                    product codes and explore the available rewards.
                </p>
            </div>

            <a href="<?= e(url('customer/register.php')) ?>"
               class="rewards-button">
                Create Free Account →
            </a>

        </div>

    </div>

</section>

<?php

require_once __DIR__ . '/../app/includes/footer.php';

?>
