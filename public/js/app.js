document.addEventListener('DOMContentLoaded', () => {
    const dashboardContent = document.querySelector('[data-dashboard-content]');

    const bindDashboardFilters = () => {
        if (!dashboardContent) {
            return;
        }

        const form = dashboardContent.querySelector('[data-dashboard-filter]');
        const presetSelect = dashboardContent.querySelector('[data-filter-preset]');
        const monthSelect = dashboardContent.querySelector('[data-filter-month]');
        const fromDateInput = dashboardContent.querySelector('[data-range-date="from"]');
        const toDateInput = dashboardContent.querySelector('[data-range-date="to"]');
        const rangeTrigger = dashboardContent.querySelector('[data-range-trigger]');
        const rangePopover = dashboardContent.querySelector('[data-range-popover]');

        if (!form || !presetSelect || !monthSelect || !fromDateInput || !toDateInput || !rangeTrigger || !rangePopover) {
            return;
        }

        const submitFilters = async () => {
            const params = new URLSearchParams(new FormData(form));
            const url = `${form.action}?${params.toString()}`;
            const response = await fetch(url, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            if (!response.ok) {
                window.location.href = url;
                return;
            }

            dashboardContent.innerHTML = await response.text();
            window.history.replaceState({}, '', url);
            bindDashboardFilters();
        };

        const updateRangeTrigger = () => {
            if (fromDateInput.value && toDateInput.value) {
                rangeTrigger.textContent = `${fromDateInput.value} - ${toDateInput.value}`;
            } else {
                rangeTrigger.textContent = 'Pick date range';
            }
        };

        presetSelect.addEventListener('change', () => {
            if (presetSelect.value !== '') {
                fromDateInput.value = '';
                toDateInput.value = '';
            }
            submitFilters();
        });

        monthSelect.addEventListener('change', () => {
            presetSelect.value = '';
            fromDateInput.value = '';
            toDateInput.value = '';
            updateRangeTrigger();
            submitFilters();
        });

        rangeTrigger.addEventListener('click', () => {
            rangePopover.hidden = !rangePopover.hidden;
            if (!rangePopover.hidden && typeof fromDateInput.showPicker === 'function') {
                fromDateInput.showPicker();
            }
        });

        [fromDateInput, toDateInput].forEach((input) => {
            input.addEventListener('focus', () => {
                if (typeof input.showPicker === 'function') {
                    input.showPicker();
                }
            });

            input.addEventListener('change', () => {
                presetSelect.value = '';
                updateRangeTrigger();

                if (fromDateInput.value && toDateInput.value) {
                    rangePopover.hidden = true;
                    submitFilters();
                }
            });
        });

        document.addEventListener('click', (event) => {
            if (!rangePopover.hidden && !rangePopover.contains(event.target) && !rangeTrigger.contains(event.target)) {
                rangePopover.hidden = true;
            }
        }, { once: true });
    };

    bindDashboardFilters();

    const paymentModal = document.querySelector('[data-payment-modal]');

    if (paymentModal) {
        const closePaymentModal = () => {
            paymentModal.hidden = true;
        };
        const paymentAmountInput = paymentModal.querySelector('[data-payment-amount]');
        const openPaymentModal = (button) => {
            paymentModal.hidden = false;
            paymentModal.querySelector('[data-payment-worker-id]').value = button.dataset.workerId;
            paymentModal.querySelector('[data-payment-worker-name]').textContent = button.dataset.workerName;
            paymentModal.querySelector('[data-payment-total-earned]').textContent = `€${button.dataset.totalEarned}`;
            paymentModal.querySelector('[data-payment-total-paid]').textContent = `€${button.dataset.totalPaid}`;
            paymentModal.querySelector('[data-payment-outstanding]').textContent = `€${button.dataset.outstanding}`;
            paymentModal.querySelector('[data-payment-credit]').textContent = `€${button.dataset.credit}`;

            const unpaidRow = paymentModal.querySelector('[data-payment-oldest-unpaid-row]');
            const unpaidValue = paymentModal.querySelector('[data-payment-oldest-unpaid]');

            if (button.dataset.oldestUnpaidMonth) {
                unpaidRow.hidden = false;
                unpaidValue.textContent = button.dataset.oldestUnpaidMonth;
            } else {
                unpaidRow.hidden = true;
                unpaidValue.textContent = '';
            }

            if (paymentAmountInput) {
                const suggestedAmount = parseFloat(button.dataset.outstanding || '0') > 0
                    ? button.dataset.outstanding
                    : '';
                paymentAmountInput.value = suggestedAmount;
            }
        };

        document.querySelectorAll('[data-payment-trigger]').forEach((button) => {
            button.addEventListener('click', () => openPaymentModal(button));
        });

        document.querySelector('[data-payment-worker-filter]')?.addEventListener('change', (event) => {
            event.target.form?.submit();
        });

        paymentModal.querySelector('[data-payment-close]')?.addEventListener('click', closePaymentModal);
        paymentModal.addEventListener('click', (event) => {
            if (event.target === paymentModal) {
                closePaymentModal();
            }
        });

        const autoOpenTrigger = document.querySelector('[data-payment-auto-open="true"]');
        if (autoOpenTrigger) {
            openPaymentModal(autoOpenTrigger);
        }
    }

    const sidebarToggle = document.querySelector('[data-sidebar-toggle]');
    const sidebarNav = document.querySelector('[data-sidebar-nav]');

    if (sidebarToggle && sidebarNav) {
        const syncSidebarState = () => {
            if (window.innerWidth > 640) {
                sidebarNav.hidden = false;
                sidebarToggle.setAttribute('aria-expanded', 'false');
                return;
            }

            if (!sidebarToggle.hasAttribute('data-initialized')) {
                sidebarNav.hidden = true;
            }
        };

        syncSidebarState();
        sidebarToggle.setAttribute('data-initialized', 'true');

        sidebarToggle.addEventListener('click', () => {
            const isExpanded = sidebarToggle.getAttribute('aria-expanded') === 'true';
            sidebarToggle.setAttribute('aria-expanded', isExpanded ? 'false' : 'true');
            sidebarNav.hidden = isExpanded;
        });

        window.addEventListener('resize', syncSidebarState);
    }

    const scheduleContent = document.querySelector('[data-schedule-content]');
    const modal = document.querySelector('[data-schedule-modal]');

    if (!modal) {
        return;
    }

    const modalDate = modal.querySelector('[data-modal-date]');
    const workDateInput = modal.querySelector('[data-modal-work-date]');
    const deleteForm = modal.querySelector('[data-delete-form]');
    const modalActions = modal.querySelector('[data-modal-actions]');
    const projectSelect = modal.querySelector('[data-modal-project]');
    const monthInput = modal.querySelector('[data-modal-month]');
    const rateSelect = modal.querySelector('[data-modal-rate]');
    const radioInputs = Array.from(modal.querySelectorAll('input[name="hours"]'));

    const closeModal = () => {
        modal.hidden = true;
    };

    const bindScheduleInteractions = () => {
        document.querySelectorAll('[data-schedule-trigger]').forEach((button) => {
            button.addEventListener('click', () => {
                const { date, displayDate, hours, entryId, projectId, rateOverride } = button.dataset;

                modal.hidden = false;
                modalDate.textContent = displayDate;
                workDateInput.value = date;

                radioInputs.forEach((input) => {
                    input.checked = input.value === hours;
                });

                if (projectSelect) {
                    projectSelect.value = projectId || '';
                }

                if (rateSelect) {
                    rateSelect.value = rateOverride || '';
                }

                if (monthInput) {
                    monthInput.value = new URL(window.location.href).searchParams.get('month') || monthInput.value;
                }

                if (entryId) {
                    deleteForm.hidden = false;
                    deleteForm.action = `${window.location.pathname}/${entryId}`;
                    modalActions?.classList.remove('single-action');
                } else {
                    deleteForm.hidden = true;
                    deleteForm.action = '';
                    modalActions?.classList.add('single-action');
                }
            });
        });
    };

    const bindCalendarNavigation = () => {
        if (!scheduleContent) {
            return;
        }

        scheduleContent.querySelectorAll('[data-calendar-nav]').forEach((link) => {
            link.addEventListener('click', async (event) => {
                event.preventDefault();

                const response = await fetch(link.href, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });

                if (!response.ok) {
                    window.location.href = link.href;
                    return;
                }

                const html = await response.text();
                scheduleContent.innerHTML = html;
                window.history.replaceState({}, '', link.href);
                bindCalendarNavigation();
                bindScheduleInteractions();
            });
        });
    };

    bindScheduleInteractions();
    bindCalendarNavigation();
    modal.querySelector('[data-modal-close]')?.addEventListener('click', closeModal);
    modal.addEventListener('click', (event) => {
        if (event.target === modal) {
            closeModal();
        }
    });
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && !modal.hidden) {
            closeModal();
        }
    });
});
