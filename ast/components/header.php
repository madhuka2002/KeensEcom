<header class="bg-white shadow-sm sticky-top" style="z-index: 1000;">
    <nav class="navbar navbar-expand-lg navbar-light">
        <div class="container-fluid">
            <!-- Logo -->
            <a class="navbar-brand" href="index.php" style="
                font-size: 1.75rem;
                font-weight: 800;
                color: #333;
                text-decoration: none;
                background: linear-gradient(45deg, #6a11cb 0%, #2575fc 100%);
                -webkit-background-clip: text;
                -webkit-text-fill-color: transparent;
            ">
                Keens<span style="color: #2575fc;">.lk</span>
            </a>

            <!-- Mobile Toggle -->
            <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation" style="box-shadow: none;">
                <span class="navbar-toggler-icon"></span>
            </button>

            <!-- Navigation Links -->
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0" style="gap: 1rem;">
                    <?php 
                    $pages = [
                        'index.php' => 'HOME', 
                        'about.php' => 'ABOUT', 
                        'orders.php' => 'ORDERS', 
                        'shop.php' => 'SHOP', 
                        'contact.php' => 'CONTACT'
                    ];
                    foreach($pages as $url => $name): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= $url; ?>" style="
                            color: #333;
                            font-weight: 600;
                            text-decoration: none;
                            position: relative;
                            transition: color 0.3s ease;
                            padding: 0.5rem 1rem;
                        " onmouseover="this.style.color='#2575fc'" onmouseout="this.style.color='#333'">
                            <?= $name; ?>
                        </a>
                    </li>
                    <?php endforeach; ?>
                </ul>

                <!-- Icons Section -->
                <div class="d-flex align-items-center" style="gap: 1rem;">
                    <?php
                        $count_wishlist_items = $conn->prepare("SELECT * FROM `wishlist` WHERE user_id = ?");
                        $count_wishlist_items->execute([$user_id]);
                        $total_wishlist_counts = $count_wishlist_items->rowCount();

                        $count_cart_items = $conn->prepare("SELECT * FROM `cart` WHERE user_id = ?");
                        $count_cart_items->execute([$user_id]);
                        $total_cart_counts = $count_cart_items->rowCount();
                    ?>
                    <div class="d-flex" style="gap: 0.75rem;">
                        <a href="search.php" class="text-dark text-decoration-none" style="transition: color 0.3s ease;" onmouseover="this.style.color='#2575fc'" onmouseout="this.style.color='#333'">
                            <i class="fas fa-search"></i>
                        </a>
                        <a href="wishlist.php" class="text-dark text-decoration-none" style="transition: color 0.3s ease;" onmouseover="this.style.color='#2575fc'" onmouseout="this.style.color='#333'">
                            <i class="fas fa-heart"></i>
                            <span class="badge bg-primary rounded-pill"><?= $total_wishlist_counts; ?></span>
                        </a>
                        <a href="cart.php" class="text-dark text-decoration-none" style="transition: color 0.3s ease;" onmouseover="this.style.color='#2575fc'" onmouseout="this.style.color='#333'">
                            <i class="fas fa-shopping-cart"></i>
                            <span class="badge bg-primary rounded-pill"><?= $total_cart_counts; ?></span>
                        </a>
                    </div>

                    <!-- User Dropdown -->
                    <div class="dropdown">
                        <button class="btn btn-light rounded-circle" type="button" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false" style="
                            width: 40px;
                            height: 40px;
                            display: flex;
                            align-items: center;
                            justify-content: center;
                            padding: 0;
                        ">
                            <i class="fas fa-user"></i>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown" style="min-width: 250px;">
                            <?php
                            $select_profile = $conn->prepare("SELECT * FROM `users` WHERE id = ?");
                            $select_profile->execute([$user_id]);
                            if ($select_profile->rowCount() > 0) {
                                $fetch_profile = $select_profile->fetch(PDO::FETCH_ASSOC);
                            ?>
                                <li class="dropdown-header">
                                    <h6 class="mb-0"><?= $fetch_profile["name"]; ?></h6>
                                    <small class="text-muted">Customer</small>
                                </li>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <a class="dropdown-item" href="account.php">
                                        <i class="fas fa-user-edit me-2"></i>Update Profile
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="components/user_logout.php" onclick="return confirm('Logout from the website?');">
                                        <i class="fas fa-sign-out-alt me-2 text-danger"></i>Logout
                                    </a>
                                </li>
                            <?php } else { ?>
                                <li class="dropdown-header">
                                    <h6 class="mb-0">Guest</h6>
                                    <small class="text-muted">Please Login</small>
                                </li>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <a class="dropdown-item" href="register.php">
                                        <i class="fas fa-user-plus me-2"></i>Register
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="login.php">
                                        <i class="fas fa-sign-in-alt me-2"></i>Login
                                    </a>
                                </li>
                            <?php } ?>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </nav>
</header>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>