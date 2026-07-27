        // Toggle submenus
        document.querySelectorAll('.menu-item').forEach(item => {
            if (item.dataset.menu) {
                item.addEventListener('click', function() {
                    const submenu = document.getElementById(this.dataset.menu);
                    const chevron = this.querySelector('.chevron');
                    
                    if (submenu.classList.contains('open')) {
                        submenu.classList.remove('open');
                        chevron.style.transform = 'rotate(0deg)';
                    } else {
                        submenu.classList.add('open');
                        chevron.style.transform = 'rotate(180deg)';
                    }
                    
                    // Close other submenus
                    document.querySelectorAll('.submenu').forEach(sub => {
                        if (sub.id !== this.dataset.menu && sub.classList.contains('open')) {
                            sub.classList.remove('open');
                            const otherChevron = document.querySelector(`.menu-item[data-menu="${sub.id}"] .chevron`);
                            if (otherChevron) {
                                otherChevron.style.transform = 'rotate(0deg)';
                            }
                        }
                    });
                });
            }
        });
        
        // Mobile sidebar toggle
        document.querySelector('.toggle-sidebar').addEventListener('click', function() {
            document.querySelector('.sidebar').classList.toggle('open');
        });
        
        // Menu item active state
        document.querySelectorAll('.menu-item, .submenu-item').forEach(item => {
            item.addEventListener('click', function() {
                // Remove active class from all menu items
                document.querySelectorAll('.menu-item').forEach(i => {
                    i.classList.remove('active');
                });
                
                // Add active class to clicked item
                if (this.classList.contains('menu-item')) {
                    this.classList.add('active');
                } else {
                    this.closest('.menu-item').classList.add('active');
                }
            });
        });
        
        // Simulate loading data
        setTimeout(() => {
            document.querySelectorAll('.badge').forEach(badge => {
                if (badge.classList.contains('badge-warning')) {
                    badge.textContent = "Pending Approval";
                }
            });
        }, 2000);
