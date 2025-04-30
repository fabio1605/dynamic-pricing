(function() {
    document.addEventListener('DOMContentLoaded', function () {
        const dateInput = document.getElementById('dp-date');
        const venueInput = document.getElementById('dp-venue');
    
        if (dateInput) {
            if (sessionStorage.getItem('dp-date')) {
                dateInput.value = sessionStorage.getItem('dp-date');
            }
    
            dateInput.addEventListener('change', function () {
                sessionStorage.setItem('dp-date', this.value);
    
                // Dispatch a custom event so all dynamic_price widgets can listen
                const event = new Event('dp-date-changed');
                window.dispatchEvent(event);
            });
        }
    
        if (venueInput) {
            if (sessionStorage.getItem('dp-venue')) {
                venueInput.value = sessionStorage.getItem('dp-venue');
            }
    
            venueInput.addEventListener('input', function () {
                sessionStorage.setItem('dp-venue', this.value);
            });
        }
    });

    function updatePrices(selectedDate) {
        if (!selectedDate) return;

        document.querySelectorAll('.dynamic-pricing-widget').forEach(function(widget) {
            const priceSpan = widget.querySelector('.dynamic-price');
            const originalSpan = widget.querySelector('.original-price');
            const packageData = JSON.parse(widget.getAttribute('data-package'));
            const settingsData = JSON.parse(widget.getAttribute('data-settings'));

            const selected = new Date(selectedDate + 'T00:00:00');
            if (isNaN(selected)) return;

            let price = parseFloat(packageData.base_price);
            const dayOfWeek = selected.getDay();
            const dayNumber = dayOfWeek === 0 ? 7 : dayOfWeek;
            const month = selected.getMonth() + 1;

            const midweek = settingsData.midweek_days ? settingsData.midweek_days.map(Number) : [2,3,4];
            if (midweek.includes(dayNumber)) {
                if (packageData.midweek_discount_type === 'percent') {
                    price -= price * (parseFloat(packageData.midweek_discount_value) / 100);
                } else {
                    price -= parseFloat(packageData.midweek_discount_value);
                }
            }

            const winter = settingsData.winter_months ? settingsData.winter_months.map(Number) : [11,12,1,2];
            if (winter.includes(month)) {
                if (packageData.winter_discount_type === 'percent') {
                    price -= price * (parseFloat(packageData.winter_discount_value) / 100);
                } else {
                    price -= parseFloat(packageData.winter_discount_value);
                }
            }

            price = Math.max(0, price);
            priceSpan.textContent = '£' + price.toFixed(2);

            if (originalSpan) {
                if (price < packageData.base_price) {
                    originalSpan.style.display = 'inline';
                } else {
                    originalSpan.style.display = 'none';
                }
            }
        });
    }

    document.addEventListener('DOMContentLoaded', setupDynamicPricing);
})();
