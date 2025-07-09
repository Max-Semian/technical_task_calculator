$(document).ready(function() {
    // Configuration data - ИСПРАВЛЕНО по ТЗ
    const config = {
        regions: {
            1: { name: 'Ленинградская область', maxTonns: 500 },
            2: { name: 'Московская область', maxTonns: 500 },
            3: { name: 'Свердловская область', maxTonns: 500 }
        },
        // ИСПРАВЛЕНО: правильные бренды по типам топлива согласно ТЗ
        fuelTypes: {
            benzin: {
                name: 'Бензин',
                price: 500200,
                brands: ['rosneft', 'tatneft', 'lukoil'], // Только эти бренды для бензина
                tariffs: { econom: 100, selected: 300, premium: 300 }
            },
            gas: {
                name: 'Газ',
                price: 200100,
                brands: ['shell', 'gazprom', 'bashneft'], // Только эти бренды для газа
                tariffs: { econom: 200, selected: 700, premium: 700 }
            },
            dt: {
                name: 'ДТ',
                price: 320700,
                brands: ['tatneft', 'lukoil'], // Только эти бренды для ДТ
                tariffs: { econom: 150, selected: 350, premium: 350 }
            }
        },
        brands: {
            shell: { name: 'Shell', icon: 'img/shell.png' },
            gazprom: { name: 'Газпром', icon: 'img/gazprom.png' },
            rosneft: { name: 'Роснефть', icon: 'img/rosneft.png' },
            tatneft: { name: 'Татнефть', icon: 'img/tatneft.png' },
            lukoil: { name: 'Лукойл', icon: 'img/lucoil.png' }, // ВОЗВРАЩЕНО: lucoil.png
            bashneft: { name: 'Башнефть', icon: 'img/bashneft.png' }
        },
        tariffDiscounts: {
            econom: 3,
            selected: 5,
            premium: 7
        },
        promoActions: {
            econom: [2, 5],
            selected: [5, 20],
            premium: [20, 50]
        },
        promoDescriptions: {
            2: 'Скидка на топливо',
            5: 'Скидка на мойку',
            20: 'Возврат НДС',
            50: 'Экономия на штрафах'
        }
    };

    // State - начальный бренд должен соответствовать типу топлива
    let state = {
        region: 1,
        pumping: 200,
        fuelType: 'benzin',
        brand: 'rosneft', // ИСПРАВЛЕНО: rosneft доступен для benzin
        services: [],
        tariff: 'selected',
        promoAction: 20,
        monthlyCost: 0,
        monthlyEconomy: 0,
        yearlyEconomy: 0
    };

    // Initialize calculator
    function init() {
        updateRegionLimits();
        updateBrands();
        updatePromoActions();
        updateCalculations();
        bindEvents();
    }

    // Event bindings
    function bindEvents() {
        // Region change
        $('#regionSelect').on('change', function() {
            state.region = parseInt($(this).val());
            updateRegionLimits();
            updateCalculations();
        });

        // Pumping range
        $('#pumpingRange').on('input', function() {
            state.pumping = parseInt($(this).val());
            $('#pumpingValue').text(state.pumping);
            updateCalculations();
        });

        // Fuel type toggle
        $('.fuel-btn').on('click', function() {
            $('.fuel-btn').removeClass('active');
            $(this).addClass('active');
            state.fuelType = $(this).data('fuel');
            updateBrands();
            updateCalculations();
        });

        // Brand selection
        $(document).on('click', '.brand-item', function() {
            $('.brand-item').removeClass('active');
            $(this).addClass('active');
            state.brand = $(this).data('brand');
            updateCalculations();
        });

        // Service selection
        $('.service-item').on('click', function() {
            const service = $(this).data('service');
            const index = state.services.indexOf(service);
            
            if (index > -1) {
                state.services.splice(index, 1);
                $(this).removeClass('active');
            } else {
                state.services.push(service);
                $(this).addClass('active');
            }
            
            updateCalculations();
        });

        // Promo action selection - возвращаем ограничение для disabled
        $(document).on('click', '.promo-option:not(.disabled)', function() {
            $('.promo-option').removeClass('active');
            $(this).addClass('active');
            state.promoAction = parseInt($(this).data('promo'));
            updateCalculations();
        });

        // Form submission
        $('#orderForm').on('submit', function(e) {
            e.preventDefault();
            submitOrder();
        });

        // Clear form message when modal opens
        $('#orderModal').on('show.bs.modal', function() {
            $('#formMessage').text('').removeClass('success error');
        });

        // Input validation
        $('#innInput').on('input', function() {
            this.value = this.value.replace(/\D/g, '');
            validateField(this, this.value.length === 12);
        });

        $('#phoneInput').on('input', function() {
            this.value = this.value.replace(/\D/g, '');
            validateField(this, this.value.length === 11);
        });

        $('#emailInput').on('input', function() {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            validateField(this, emailRegex.test(this.value));
        });
    }

    // Update region limits
    function updateRegionLimits() {
        const region = config.regions[state.region];
        const maxTonns = region.maxTonns;
        
        $('#pumpingRange').attr('max', maxTonns);
        
        if (state.pumping > maxTonns) {
            state.pumping = maxTonns;
            $('#pumpingValue').text(state.pumping);
            $('#pumpingRange').val(state.pumping);
        }
    }

    // Update available brands - ИСПРАВЛЕНО: правильное обновление состояния
    function updateBrands() {
        const fuel = config.fuelTypes[state.fuelType];
        
        // ИСПРАВЛЕНО: проверяем доступность бренда ДО генерации HTML
        if (!fuel.brands.includes(state.brand)) {
            state.brand = fuel.brands[0];
            console.log('Brand updated to:', state.brand); // Для отладки
        }
        
        const brandHtml = fuel.brands.map(brandKey => {
            const brand = config.brands[brandKey];
            const isActive = brandKey === state.brand ? 'active' : '';
            
            return `
                <div class="brand-item ${isActive}" data-brand="${brandKey}">
                    <div class="brand-icon ${brandKey}">
                        <img src="${brand.icon}" alt="${brand.name}" />
                    </div>
                    <span>${brand.name}</span>
                </div>
            `;
        }).join('');
        
        $('#brandSelection').html(brandHtml);
    }

    // Determine tariff based on fuel type and pumping volume
    function determineTariff() {
        const fuel = config.fuelTypes[state.fuelType];
        const pumping = state.pumping;
        
        if (pumping < fuel.tariffs.econom) {
            return 'econom';
        } else if (pumping < fuel.tariffs.selected) {
            return 'selected';
        } else {
            return 'premium';
        }
    }

    // Update promo actions - ИСПРАВЛЕНО: правильная логика по ТЗ
    function updatePromoActions() {
        const tariff = determineTariff();
        
        // Доступные промо-акции по тарифам согласно ТЗ
        const availablePromos = config.promoActions[tariff];
        const allPromos = [50, 20, 5, 2];
        
        const promoHtml = allPromos.map(promo => {
            const isActive = promo === state.promoAction ? 'active' : '';
            const isAvailable = availablePromos.includes(promo);
            const disabledClass = isAvailable ? '' : 'disabled';
            const description = config.promoDescriptions[promo];
            
            return `
                <div class="promo-option ${isActive} ${disabledClass}" data-promo="${promo}">
                    <div class="promo-percent">${promo}%</div>
                    <div class="promo-description">${description}</div>
                </div>
            `;
        }).join('');
        
        $('#promoOptions').html(promoHtml);
        
        // Если текущая промо-акция недоступна для нового тарифа, выбираем максимальную доступную
        if (!availablePromos.includes(state.promoAction)) {
            state.promoAction = Math.max(...availablePromos);
        }
        
        state.tariff = tariff;
    }

    // Calculate costs and savings
    function calculateCosts() {
        const fuel = config.fuelTypes[state.fuelType];
        const baseCost = fuel.price * state.pumping;
        
        const tariffDiscount = config.tariffDiscounts[state.tariff];
        const promoDiscount = state.promoAction;
        
        const tariffDiscountAmount = (baseCost * tariffDiscount) / 100;
        const promoDiscountAmount = (baseCost * promoDiscount) / 100;
        
        state.monthlyCost = baseCost - tariffDiscountAmount - promoDiscountAmount;
        state.monthlyEconomy = tariffDiscountAmount + promoDiscountAmount;
        state.yearlyEconomy = state.monthlyEconomy * 12;
    }

    // Update all calculations
    function updateCalculations() {
        updatePromoActions();
        calculateCosts();
        updateUI();
        
        // Send AJAX request to backend
        sendCalculationRequest();
    }

    // Update UI with new values
    function updateUI() {
        // Update tariff display
        const tariffNames = {
            econom: 'Эконом',
            selected: 'Избранный',
            premium: 'Премиум'
        };
        
        const tariffName = tariffNames[state.tariff];
        $('#currentTariff').html(`★ ${tariffName}`);
        $('#orderTariffName').text(tariffName);
        $('#modalTariffName').text(tariffName);
        $('.order-modal-btn').text(`Заказать тариф «${tariffName}»`);
        
        // Update savings
        $('#monthSaving').text(`${formatNumber(state.monthlyEconomy)} ₽`);
        $('#yearSaving').text(`${formatNumber(state.yearlyEconomy)} ₽`);
        
        // Update promo action active state
        $('.promo-option').removeClass('active');
        $(`.promo-option[data-promo="${state.promoAction}"]`).addClass('active');
    }

    // Format number with thousands separator
    function formatNumber(num) {
        return new Intl.NumberFormat('ru-RU').format(Math.round(num));
    }

    // Send calculation request to backend
    function sendCalculationRequest() {
        const requestData = {
            action: 'calculate',
            region: state.region,
            pumping: state.pumping,
            fuelType: state.fuelType,
            brand: state.brand,
            services: state.services,
            tariff: state.tariff,
            promoAction: state.promoAction
        };
        
        $.ajax({
            url: 'calculator_backend.php',
            method: 'POST',
            data: requestData,
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    // Update calculations from backend if needed
                    if (response.calculations) {
                        state.monthlyCost = response.calculations.monthlyCost;
                        state.monthlyEconomy = response.calculations.monthlyEconomy;
                        state.yearlyEconomy = response.calculations.yearlyEconomy;
                        
                        $('#monthSaving').text(`${formatNumber(state.monthlyEconomy)} ₽`);
                        $('#yearSaving').text(`${formatNumber(state.yearlyEconomy)} ₽`);
                    }
                }
            },
            error: function() {
                console.log('Calculation request failed');
            }
        });
    }

    // Validate form field
    function validateField(field, isValid) {
        const $field = $(field);
        
        if (isValid) {
            $field.removeClass('is-invalid').addClass('is-valid');
        } else {
            $field.removeClass('is-valid').addClass('is-invalid');
        }
        
        return isValid;
    }

    // Submit order
    function submitOrder() {
        // Validate form
        const inn = $('#innInput').val();
        const phone = $('#phoneInput').val();
        const email = $('#emailInput').val();
        const agreement = $('#agreementCheck').is(':checked');
        
        const innValid = validateField($('#innInput')[0], inn.length === 12 && /^\d{12}$/.test(inn));
        const phoneValid = validateField($('#phoneInput')[0], phone.length === 11 && /^\d{11}$/.test(phone));
        const emailValid = validateField($('#emailInput')[0], /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email));
        const agreementValid = agreement;
        
        if (!agreementValid) {
            $('#agreementCheck').addClass('is-invalid');
        } else {
            $('#agreementCheck').removeClass('is-invalid');
        }
        
        if (!innValid || !phoneValid || !emailValid || !agreementValid) {
            showFormMessage('error', 'Пожалуйста, заполните все поля корректно');
            return;
        }
        
        // Prepare data for sending
        const postData = {
            action: 'submit_order',
            inn: inn,
            phone: phone,
            email: email,
            agreement: agreement,
            calculationData: {
                region: state.region,
                regionName: config.regions[state.region].name,
                pumping: state.pumping,
                fuelType: state.fuelType,
                fuelName: config.fuelTypes[state.fuelType].name,
                brand: state.brand,
                brandName: config.brands[state.brand].name,
                services: state.services,
                tariff: state.tariff,
                promoAction: state.promoAction,
                monthlyCost: state.monthlyCost,
                monthlyEconomy: state.monthlyEconomy,
                yearlyEconomy: state.yearlyEconomy,
                totalDiscount: config.tariffDiscounts[state.tariff] + state.promoAction
            }
        };
        
        console.log('Sending data:', postData);
        
        // Show loading state
        $('.order-modal-btn').prop('disabled', true).text('Отправка...');
        
        // Send to backend
        $.ajax({
            url: `${API_URL}/calculator_backend.php`,
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify(postData),
            processData: false,
            success: function(response) {
                console.log('Response:', response);
                if (response.success) {
                    showFormMessage('success', 'Спасибо! Успешно отправлено.');
                    $('#orderForm')[0].reset();
                    $('.form-control').removeClass('is-valid is-invalid');
                    $('#agreementCheck').removeClass('is-invalid');
                    
                    // Close modal after 2 seconds
                    setTimeout(function() {
                        $('#orderModal').modal('hide');
                        // Clear message when modal closes
                        setTimeout(function() {
                            $('#formMessage').text('').removeClass('success error');
                        }, 300);
                    }, 2000);
                } else {
                    showFormMessage('error', `Ошибка: ${response.message}`);
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', {xhr, status, error});
                showFormMessage('error', 'Ошибка: Не удалось отправить данные');
            },
            complete: function() {
                const tariffNames = {
                    econom: 'Эконом',
                    selected: 'Избранный',
                    premium: 'Премиум'
                };
                const currentTariffName = tariffNames[state.tariff];
                $('.order-modal-btn').prop('disabled', false).text(`Заказать тариф «${currentTariffName}»`);
            }
        });
    }

    // Show form message
    function showFormMessage(type, message) {
        const $message = $('#formMessage');
        $message.removeClass('success error').addClass(type).text(message);
        
        if (type === 'success') {
            setTimeout(function() {
                $message.text('').removeClass('success error');
            }, 5000);
        }
    }

    // Initialize calculator on page load
    init();
});

// Custom range slider styling
function updateRangeBackground() {
    const range = document.getElementById('pumpingRange');
    const value = ((range.value - range.min) / (range.max - range.min)) * 100;
    range.style.background = `linear-gradient(to right, #FFE66D 0%, #FFE66D ${value}%, #DDD ${value}%, #DDD 100%)`;
}

// Update range background on input
$(document).on('input', '#pumpingRange', updateRangeBackground);

// Initial range background
$(document).ready(function() {
    setTimeout(updateRangeBackground, 100);
});