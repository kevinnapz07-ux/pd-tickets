import { Html5Qrcode } from 'html5-qrcode';

(() => {
    const storageKey = 'pdug-theme-mode';
    const choices = ['light', 'dark', 'system'];
    const media = window.matchMedia('(prefers-color-scheme: dark)');
    const root = document.documentElement;

    const syncFilamentTheme = () => {
        if (! document.body?.classList.contains('fi-panel-admin')) {
            return false;
        }

        const theme = root.classList.contains('dark') ? 'dark' : 'light';
        root.dataset.theme = theme;
        root.style.colorScheme = theme;

        return true;
    };

    const normalize = (mode) => choices.includes(mode) ? mode : 'system';
    const resolve = (mode) => mode === 'system' ? (media.matches ? 'dark' : 'light') : mode;

    const updateButtons = (mode) => {
        document.querySelectorAll('[data-theme-choice]').forEach((button) => {
            const active = button.dataset.themeChoice === mode;
            button.classList.toggle('is-active', active);
            button.setAttribute('aria-pressed', active ? 'true' : 'false');
        });
    };

    const applyTheme = (mode) => {
        const normalized = normalize(mode);
        const theme = resolve(normalized);

        root.dataset.themeMode = normalized;
        root.dataset.theme = theme;
        root.style.colorScheme = theme;
        updateButtons(normalized);
    };

    const setTheme = (mode) => {
        const normalized = normalize(mode);
        localStorage.setItem(storageKey, normalized);
        applyTheme(normalized);
    };

    const setupHowToOrderModal = () => {
        const modal = document.querySelector('[data-how-modal]');
        const openButtons = document.querySelectorAll('[data-how-open]');
        const closeButtons = document.querySelectorAll('[data-how-close]');
        const description = document.querySelector('meta[name="description"]');
        const modalTitle = document.querySelector('meta[name="how-to-order-title"]')?.content;
        const modalDescription = document.querySelector('meta[name="how-to-order-description"]')?.content;
        const originalTitle = document.title;
        const originalDescription = description?.content;
        let openingButton = null;

        if (! modal || openButtons.length === 0) {
            return;
        }

        const openModal = (event) => {
            openingButton = event?.currentTarget || document.activeElement;
            modal.setAttribute('aria-hidden', 'false');
            document.body.classList.add('modal-open');
            document.title = modalTitle || originalTitle;
            if (description && modalDescription) description.content = modalDescription;
            modal.querySelector('[data-how-close]')?.focus();
        };

        const closeModal = () => {
            modal.setAttribute('aria-hidden', 'true');
            document.body.classList.remove('modal-open');
            document.title = originalTitle;
            if (description && originalDescription) description.content = originalDescription;
            openingButton?.focus();
            openingButton = null;
        };

        openButtons.forEach((button) => {
            button.addEventListener('click', openModal);
        });

        closeButtons.forEach((button) => {
            button.addEventListener('click', closeModal);
        });

        modal.addEventListener('click', (event) => {
            if (event.target === modal) {
                closeModal();
            }
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && modal.getAttribute('aria-hidden') === 'false') {
                closeModal();
            }

            if (event.key === 'Tab' && modal.getAttribute('aria-hidden') === 'false') {
                const focusable = [...modal.querySelectorAll('button:not([disabled]), a[href], input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])')]
                    .filter((element) => element.offsetParent !== null);

                if (focusable.length === 0) {
                    event.preventDefault();
                    modal.querySelector('.how-modal')?.focus();
                    return;
                }

                const first = focusable[0];
                const last = focusable[focusable.length - 1];

                if (event.shiftKey && document.activeElement === first) {
                    event.preventDefault();
                    last.focus();
                } else if (! event.shiftKey && document.activeElement === last) {
                    event.preventDefault();
                    first.focus();
                }
            }
        });
    };

    const setupToasts = () => {
        document.querySelectorAll('[data-toast]').forEach((toast) => {
            window.setTimeout(() => {
                toast.classList.add('is-hiding');
                window.setTimeout(() => toast.remove(), 260);
            }, 4200);
        });
    };

    const setupAccountMenus = () => {
        const menus = document.querySelectorAll('[data-account-menu]');

        if (menus.length === 0) {
            return;
        }

        const closeAll = () => {
            menus.forEach((menu) => {
                menu.classList.remove('is-open');
                menu.querySelector('[data-account-toggle]')?.setAttribute('aria-expanded', 'false');
            });
        };

        menus.forEach((menu) => {
            const toggle = menu.querySelector('[data-account-toggle]');

            toggle?.addEventListener('click', (event) => {
                event.stopPropagation();
                const isOpen = menu.classList.contains('is-open');
                closeAll();
                menu.classList.toggle('is-open', ! isOpen);
                toggle.setAttribute('aria-expanded', ! isOpen ? 'true' : 'false');
            });
        });

        document.addEventListener('click', (event) => {
            if (! event.target.closest('[data-account-menu]')) {
                closeAll();
            }
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                closeAll();
            }
        });
    };

    const setupMobileMenu = () => {
        const toggle = document.querySelector('[data-mobile-menu-toggle]');
        const menu = document.querySelector('[data-mobile-menu]');

        if (! toggle || ! menu) {
            return;
        }

        const closeMenu = () => {
            document.body.classList.remove('mobile-menu-open');
            toggle.setAttribute('aria-expanded', 'false');
            toggle.setAttribute('aria-label', 'Buka menu navigasi');
        };

        toggle.addEventListener('click', () => {
            const isOpen = document.body.classList.toggle('mobile-menu-open');
            toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
            toggle.setAttribute('aria-label', isOpen ? 'Tutup menu navigasi' : 'Buka menu navigasi');
        });

        menu.querySelectorAll('a, [data-how-open]').forEach((item) => {
            item.addEventListener('click', closeMenu);
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                closeMenu();
            }
        });

        window.matchMedia('(min-width: 901px)').addEventListener('change', (event) => {
            if (event.matches) {
                closeMenu();
            }
        });
    };

    const setupPasswordToggles = () => {
        document.querySelectorAll('[data-password-toggle]').forEach((button) => {
            const field = button.closest('.password-field');
            const input = field?.querySelector('[data-password-input]');

            if (! field || ! input) {
                return;
            }

            button.addEventListener('click', () => {
                const isVisible = input.type === 'text';
                input.type = isVisible ? 'password' : 'text';
                field.classList.toggle('is-visible', ! isVisible);
                button.setAttribute('aria-label', isVisible ? 'Lihat password' : 'Sembunyikan password');
                button.setAttribute('title', isVisible ? 'Lihat password' : 'Sembunyikan password');
            });
        });
    };

    const setupParticipantTypeFields = () => {
        document.querySelectorAll('[data-participant-type]').forEach((select) => {
            const form = select.closest('form');
            const categories = form?.querySelectorAll('[data-registration-category]');

            if (! form || ! categories || categories.length === 0) {
                return;
            }

            const updateFields = () => {
                categories.forEach((category) => {
                    const isActive = category.dataset.registrationCategory === select.value;
                    category.setAttribute('aria-hidden', isActive ? 'false' : 'true');
                    category.querySelectorAll('[data-category-field]').forEach((input) => {
                        input.disabled = ! isActive;
                        input.required = isActive && input.dataset.required === 'true';
                    });
                });
            };

            select.addEventListener('change', updateFields);
            updateFields();
        });
    };

    const setupCustomRegistrationFields = () => {
        const openCategoryPanel = (categoryTab) => {
            const eventCard = categoryTab.closest('.event-schema-card');
            const panelId = categoryTab.getAttribute('aria-controls');
            const isOpen = categoryTab.getAttribute('aria-selected') === 'true';

            eventCard?.querySelectorAll('[data-schema-category-tab]').forEach((tab) => {
                tab.setAttribute('aria-selected', ! isOpen && tab === categoryTab ? 'true' : 'false');
            });
            eventCard?.querySelectorAll('[data-schema-category-panel]').forEach((panel) => {
                panel.hidden = isOpen || panel.id !== panelId;
            });
        };

        const syncCategoryStatus = (panel) => {
            const eventCard = panel.closest('.event-schema-card');
            const tab = eventCard?.querySelector(`[aria-controls="${panel.id}"]`);
            const enabledInput = panel.querySelector('input[name$="[enabled]"]');
            const status = tab?.querySelector('[data-schema-category-status]');
            const statusText = tab?.querySelector('.sr-only');
            const toggleLabel = panel.querySelector('[data-category-toggle-label]');

            status?.classList.toggle('is-enabled', Boolean(enabledInput?.checked));
            tab?.classList.toggle('is-enabled', Boolean(enabledInput?.checked));
            if (statusText) {
                statusText.textContent = enabledInput?.checked ? 'aktif' : 'nonaktif';
            }
            if (toggleLabel) {
                toggleLabel.textContent = enabledInput?.checked ? 'Kategori Aktif' : 'Kategori Nonaktif';
            }
        };

        const syncFormPreview = (panel) => {
            const preview = panel.querySelector('[data-preview-fields]');

            if (! preview) {
                return;
            }

            const selectedFields = [...panel.querySelectorAll('.schema-field-button input:checked')]
                .map((input) => ({
                    label: input.dataset.previewLabel,
                    required: input.dataset.primaryField === 'true',
                }));
            const customFields = [...panel.querySelectorAll('[data-custom-preview-field]')]
                .map((input) => input.value.trim())
                .filter(Boolean)
                .map((label) => ({ label: label.replaceAll('_', ' '), required: false }));
            const fields = [...selectedFields, ...customFields];

            preview.replaceChildren();

            if (fields.length === 0) {
                const empty = document.createElement('p');
                empty.className = 'schema-preview-empty';
                empty.textContent = 'Belum ada field yang dipilih.';
                preview.append(empty);
                return;
            }

            fields.forEach((field) => {
                const item = document.createElement('div');
                const label = document.createElement('span');
                const input = document.createElement('span');

                item.className = 'schema-preview-field';
                label.textContent = `${field.label}${field.required ? ' *' : ''}`;
                input.setAttribute('aria-hidden', 'true');
                item.append(label, input);
                preview.append(item);
            });
        };

        document.querySelectorAll('[data-schema-category-panel]').forEach((panel) => {
            syncCategoryStatus(panel);
            syncFormPreview(panel);
        });

        const shouldBindRegistrationBuilderEvents = root.dataset.registrationBuilderEvents !== 'ready';

        if (shouldBindRegistrationBuilderEvents) {
            root.dataset.registrationBuilderEvents = 'ready';

        document.addEventListener('click', (event) => {
            const categoryTab = event.target.closest('[data-schema-category-tab]');
            const addCategoryButton = event.target.closest('[data-add-category]');
            const removeCategoryButton = event.target.closest('[data-remove-category]');
            const addButton = event.target.closest('[data-add-custom-field]');
            const removeButton = event.target.closest('[data-remove-custom-field]');

            if (categoryTab) {
                openCategoryPanel(categoryTab);
            }

            if (addCategoryButton) {
                const eventCard = addCategoryButton.closest('.event-schema-card');
                const nextCategoryTab = eventCard?.querySelector('[data-additional-category][hidden]');

                if (! nextCategoryTab) {
                    addCategoryButton.hidden = true;
                    return;
                }

                nextCategoryTab.hidden = false;
                const panel = eventCard.querySelector(`#${nextCategoryTab.getAttribute('aria-controls')}`);
                const enabledInput = panel?.querySelector('input[name$="[enabled]"]');
                const removeControl = eventCard.querySelector(
                    `[data-remove-category][aria-controls="${nextCategoryTab.getAttribute('aria-controls')}"]`,
                );

                if (removeControl) {
                    removeControl.hidden = false;
                }

                if (enabledInput) {
                    enabledInput.checked = true;
                    syncCategoryStatus(panel);
                }

                openCategoryPanel(nextCategoryTab);
                addCategoryButton.hidden = ! eventCard.querySelector('[data-additional-category][hidden]');
            }

            if (removeCategoryButton) {
                const eventCard = removeCategoryButton.closest('.event-schema-card');
                const panelId = removeCategoryButton.getAttribute('aria-controls');
                const panel = eventCard?.querySelector(`#${panelId}`);
                const categoryTabToRemove = eventCard?.querySelector(
                    `[data-additional-category][aria-controls="${panelId}"]`,
                );
                const enabledInput = panel?.querySelector('input[name$="[enabled]"]');
                const addControl = eventCard?.querySelector('[data-add-category]');

                if (enabledInput) {
                    enabledInput.checked = false;
                    syncCategoryStatus(panel);
                }
                if (categoryTabToRemove) {
                    categoryTabToRemove.hidden = true;
                    categoryTabToRemove.setAttribute('aria-selected', 'false');
                }
                if (panel) {
                    panel.hidden = true;
                }
                removeCategoryButton.hidden = true;
                if (addControl) {
                    addControl.hidden = false;
                }
            }

            if (addButton) {
                const card = addButton.closest('.schema-custom-field');
                const list = card?.querySelector('[data-custom-field-list]');
                const fieldName = addButton.dataset.fieldName;

                if (! list || ! fieldName) {
                    return;
                }

                const row = document.createElement('div');
                row.className = 'schema-custom-field-row';
                row.innerHTML = `
                    <input name="${fieldName}" placeholder="Contoh: asal_gereja" data-custom-preview-field>
                    <button type="button" data-remove-custom-field>Hapus</button>
                `;
                list.append(row);
                row.querySelector('input')?.focus();
            }

            if (removeButton) {
                const panel = removeButton.closest('[data-schema-category-panel]');
                removeButton.closest('.schema-custom-field-row')?.remove();
                if (panel) {
                    syncFormPreview(panel);
                }
            }
        });

        document.addEventListener('change', (event) => {
            const enabledInput = event.target.closest('[data-schema-category-panel] input[name$="[enabled]"]');
            const categoryName = event.target.closest('[data-schema-category-panel] .schema-category-name input');
            const registrationField = event.target.closest('[data-schema-category-panel] .schema-field-button input');

            if (enabledInput) {
                syncCategoryStatus(enabledInput.closest('[data-schema-category-panel]'));
            }

            if (categoryName) {
                const panel = categoryName.closest('[data-schema-category-panel]');
                const tabLabel = panel.closest('.event-schema-card')
                    ?.querySelector(`[aria-controls="${panel.id}"] [data-schema-category-label]`);

                if (tabLabel) {
                    tabLabel.textContent = categoryName.value.trim() || 'Kategori tanpa nama';
                }

                const panelTitle = panel.querySelector('[data-schema-panel-title]');
                if (panelTitle) {
                    panelTitle.textContent = categoryName.value.trim() || 'Kategori tanpa nama';
                }
            }

            if (registrationField) {
                syncFormPreview(registrationField.closest('[data-schema-category-panel]'));
            }
        });

        document.addEventListener('input', (event) => {
            const customField = event.target.closest('[data-schema-category-panel] [data-custom-preview-field]');

            if (customField) {
                syncFormPreview(customField.closest('[data-schema-category-panel]'));
            }
        });
        }

        const websiteSettingsForm = document.querySelector('[data-website-settings-form]');
        const formChangeStatus = websiteSettingsForm?.querySelector('[data-form-change-status]');

        if (websiteSettingsForm && formChangeStatus && websiteSettingsForm.dataset.settingsInitialized !== 'true') {
            websiteSettingsForm.dataset.settingsInitialized = 'true';
            const tabs = [...websiteSettingsForm.querySelectorAll('[data-cms-tab]')];
            const panels = [...websiteSettingsForm.querySelectorAll('[data-cms-panel]')];
            const tabSelect = websiteSettingsForm.querySelector('[data-cms-tab-select]');
            const submitButton = websiteSettingsForm.querySelector('[data-settings-submit]');
            let isDirty = false;
            let isSubmitting = false;

            const markAsChanged = () => {
                isDirty = true;
                formChangeStatus.textContent = 'Ada perubahan yang belum disimpan.';
                formChangeStatus.classList.add('has-unsaved-changes');
            };

            const markAsSaved = () => {
                isDirty = false;
                formChangeStatus.textContent = 'Semua perubahan yang tersimpan akan diterapkan ke website.';
                formChangeStatus.classList.remove('has-unsaved-changes');
            };

            const syncPreview = (source) => {
                const key = source.dataset.previewSource;
                if (! key) return;
                websiteSettingsForm.querySelectorAll(`[data-preview-target="${key}"]`).forEach((target) => {
                    target.textContent = source.value.trim() || '-';
                });
            };

            const syncCounter = (field) => {
                const counter = field.parentElement?.querySelector('[data-character-counter]');
                if (! counter) return;
                counter.textContent = field.maxLength > 0
                    ? `${field.value.length}/${field.maxLength} karakter`
                    : `${field.value.length} karakter`;
            };

            const syncVisuals = () => {
                websiteSettingsForm.querySelectorAll('[data-preview-source]').forEach(syncPreview);
                websiteSettingsForm.querySelectorAll('input, textarea').forEach(syncCounter);
            };

            const activateTab = (tabName, confirmChange = true) => {
                const currentTab = tabs.find((tab) => tab.getAttribute('aria-selected') === 'true')?.dataset.cmsTab;
                if (currentTab === tabName) return true;
                if (confirmChange && isDirty && ! window.confirm('Perubahan belum disimpan. Apakah Anda yakin ingin meninggalkan halaman?')) {
                    if (tabSelect) tabSelect.value = currentTab;
                    return false;
                }
                tabs.forEach((tab) => tab.setAttribute('aria-selected', tab.dataset.cmsTab === tabName ? 'true' : 'false'));
                panels.forEach((panel) => { panel.hidden = panel.dataset.cmsPanel !== tabName; });
                if (tabSelect) tabSelect.value = tabName;
                return true;
            };

            tabs.forEach((tab) => tab.addEventListener('click', () => activateTab(tab.dataset.cmsTab)));
            tabSelect?.addEventListener('change', () => activateTab(tabSelect.value));
            websiteSettingsForm.querySelectorAll('[data-cms-open-tab]').forEach((button) => {
                button.addEventListener('click', () => activateTab(button.dataset.cmsOpenTab));
            });

            websiteSettingsForm.addEventListener('input', (event) => {
                markAsChanged();
                syncPreview(event.target);
                syncCounter(event.target);
            });
            websiteSettingsForm.addEventListener('change', (event) => {
                if (! event.target.matches('[data-cms-tab-select]')) markAsChanged();
                syncPreview(event.target);
                syncCounter(event.target);
            });
            websiteSettingsForm.addEventListener('click', (event) => {
                if (event.target.closest('[data-add-category], [data-remove-category], [data-add-custom-field], [data-remove-custom-field]')) {
                    markAsChanged();
                }
            });
            websiteSettingsForm.addEventListener('reset', () => {
                window.setTimeout(() => {
                    markAsSaved();
                    syncVisuals();
                }, 0);
            });
            websiteSettingsForm.addEventListener('submit', () => {
                isSubmitting = true;
                submitButton?.classList.add('is-loading');
                submitButton?.setAttribute('aria-busy', 'true');
            });
            websiteSettingsForm.addEventListener('invalid', (event) => {
                const invalidPanel = event.target.closest('[data-cms-panel]');

                if (invalidPanel?.dataset.cmsPanel) {
                    activateTab(invalidPanel.dataset.cmsPanel, false);
                }
            }, true);
            window.addEventListener('beforeunload', (event) => {
                if (! isDirty || isSubmitting) return;
                event.preventDefault();
                event.returnValue = '';
            });

            activateTab('identity', false);
            syncVisuals();
        }
    };

    const setupPricingFields = () => {
        document.querySelectorAll('[data-pricing-field]').forEach((field) => {
            const select = field.querySelector('[data-pricing-type]');
            const wrapper = field.querySelector('[data-price-wrapper]');
            const input = field.querySelector('[data-price-input]');

            if (! select || ! wrapper || ! input) {
                return;
            }

            const update = () => {
                const isPaid = select.value === 'paid';
                wrapper.hidden = ! isPaid;
                input.required = isPaid;

                if (! isPaid) {
                    input.value = '';
                }
            };

            select.addEventListener('change', update);
            update();
        });
    };

    const setupTicketScanner = () => {
        const reader = document.querySelector('[data-ticket-scanner]');
        const reference = document.querySelector('[data-ticket-reference]');
        const form = document.querySelector('[data-ticket-checkin-form]');
        const submit = document.querySelector('[data-ticket-submit]');
        const loading = document.querySelector('[data-ticket-scanner-loading]');
        const result = document.querySelector('[data-ticket-result]');
        const resultIcon = document.querySelector('[data-ticket-result-icon]');
        const resultTitle = document.querySelector('[data-ticket-result-title]');
        const resultMessage = document.querySelector('[data-ticket-result-message]');
        const resultDetails = document.querySelector('[data-ticket-result-details]');
        const scanAgain = document.querySelector('[data-ticket-scan-again]');

        if (! reader || ! reference || ! form || form.dataset.scannerBound) return;

        form.dataset.scannerBound = 'true';
        const scanner = new Html5Qrcode(reader.id);
        let processing = false;
        let cameraAvailable = true;

        const startScanner = async () => {
            if (! cameraAvailable) return;

            try {
                await scanner.start(
                { facingMode: 'environment' },
                { fps: 10, qrbox: { width: 240, height: 240 } },
                async (decodedText) => {
                    if (processing) return;
                    reference.value = decodedText;
                    await processCheckIn();
                },
                () => {},
                );
            } catch (_) {
                cameraAvailable = false;
                reader.innerHTML = '<p class="ticket-scanner-fallback">Kamera tidak tersedia. Gunakan input kode tiket manual.</p>';
            }
        };

        const stopScanner = async () => {
            try {
                await scanner.stop();
            } catch (_) {}
        };

        const showResult = (success, payload) => {
            resultIcon.textContent = success ? '✓' : '!';
            resultIcon.classList.toggle('is-success', success);
            resultIcon.classList.toggle('is-error', ! success);
            resultTitle.textContent = success ? 'Check-in Berhasil' : 'Check-in Gagal';
            resultMessage.textContent = payload.message;
            resultDetails.hidden = ! success;

            if (success) {
                resultDetails.querySelector('[data-ticket-participant]').textContent = payload.participant_name;
                resultDetails.querySelector('[data-ticket-event]').textContent = payload.event_name;
                resultDetails.querySelector('[data-ticket-code]').textContent = payload.ticket_code;
                resultDetails.querySelector('[data-ticket-time]').textContent = payload.checked_in_at;
            }

            result.hidden = false;
            document.body.classList.add('modal-open');
            scanAgain.focus();
        };

        const processCheckIn = async () => {
            if (processing || ! reference.value.trim()) return;

            processing = true;
            submit.disabled = true;
            loading.hidden = false;
            await stopScanner();

            try {
                const response = await fetch(form.action, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: new FormData(form),
                });
                const payload = await response.json();
                showResult(response.ok, payload);
            } catch (_) {
                showResult(false, { message: 'Tiket belum dapat diverifikasi. Periksa koneksi lalu coba lagi.' });
            } finally {
                loading.hidden = true;
            }
        };

        form.addEventListener('submit', (event) => {
            event.preventDefault();
            processCheckIn();
        });

        scanAgain?.addEventListener('click', async () => {
            result.hidden = true;
            document.body.classList.remove('modal-open');
            reference.value = '';
            submit.disabled = false;
            processing = false;
            await startScanner();
            if (! cameraAvailable) reference.focus();
        });

        Html5Qrcode.getCameras()
            .then(startScanner)
            .catch(() => {
                cameraAvailable = false;
                reader.innerHTML = '<p class="ticket-scanner-fallback">Kamera tidak tersedia. Gunakan input kode tiket manual.</p>';
            });
    };

    const setupPasswordResetRequest = () => {
        const form = document.querySelector('[data-password-reset-request]');
        const submit = form?.querySelector('[data-reset-submit]');
        const label = form?.querySelector('[data-reset-submit-label]');

        if (! form || ! submit || ! label || form.dataset.loadingBound) return;

        form.dataset.loadingBound = 'true';
        form.addEventListener('submit', () => {
            if (! form.checkValidity()) return;

            submit.disabled = true;
            submit.classList.add('is-loading');
            submit.setAttribute('aria-busy', 'true');
            label.textContent = 'Mengirim...';
        });
    };

    const setupTicketDetails = () => {
        document.querySelectorAll('[data-expandable-description]').forEach((description) => {
            const toggle = description.querySelector('[data-description-toggle]');
            const content = description.querySelector('p');

            if (! toggle || ! content || description.dataset.expandBound) return;

            description.dataset.expandBound = 'true';
            toggle.addEventListener('click', () => {
                const expanded = description.classList.toggle('is-expanded');
                toggle.textContent = expanded ? 'Tampilkan Lebih Sedikit' : 'Lihat Selengkapnya';
                toggle.setAttribute('aria-expanded', expanded ? 'true' : 'false');
            });
        });

        document.querySelectorAll('[data-participant-accordion]').forEach((accordion) => {
            const toggle = accordion.querySelector('[data-accordion-toggle]');
            const panel = accordion.querySelector('[data-accordion-panel]');

            if (! toggle || ! panel || accordion.dataset.accordionBound) return;

            accordion.dataset.accordionBound = 'true';
            toggle.addEventListener('click', () => {
                const expanded = toggle.getAttribute('aria-expanded') === 'true';
                toggle.setAttribute('aria-expanded', expanded ? 'false' : 'true');
                panel.hidden = expanded;
                accordion.classList.toggle('is-expanded', ! expanded);
            });
        });
    };

    const initialize = () => {
        setupCustomRegistrationFields();
    if (syncFilamentTheme()) {
        new MutationObserver(syncFilamentTheme).observe(root, {
            attributes: true,
            attributeFilter: ['class'],
        });
    } else {
        applyTheme(localStorage.getItem(storageKey) || 'system');
    }
        setupHowToOrderModal();
        setupToasts();
        setupAccountMenus();
        setupMobileMenu();
        setupPasswordToggles();
        setupParticipantTypeFields();
        setupPricingFields();
        setupTicketScanner();
        setupPasswordResetRequest();
        setupTicketDetails();
        document.querySelectorAll('form[data-disable-submit]').forEach((form) => {
            if (form.dataset.submitBound) return;
            form.dataset.submitBound = 'true';
            form.addEventListener('submit', () => {
                const button = form.querySelector('button[type="submit"]');
                if (!button || !form.checkValidity()) return;
                button.disabled = true;
                button.setAttribute('aria-busy', 'true');
                button.textContent = 'Memproses...';
            });
        });

        document.querySelectorAll('[data-theme-choice]').forEach((button) => {
            button.addEventListener('click', () => setTheme(button.dataset.themeChoice));
        });
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initialize, { once: true });
    } else {
        initialize();
    }

    document.addEventListener('livewire:navigated', () => {
        syncFilamentTheme();
        setupCustomRegistrationFields();
    });

    media.addEventListener('change', () => {
        if (normalize(localStorage.getItem(storageKey)) === 'system') {
            applyTheme('system');
        }
    });
})();
