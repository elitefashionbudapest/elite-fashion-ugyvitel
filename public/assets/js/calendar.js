/**
 * Elite Fashion - Beosztas Naptar (FullCalendar)
 */

let calendar = null;
let calendarConfig = {};

/**
 * Naptar inicializalasa
 */
function initScheduleCalendar(config) {
    calendarConfig = config;

    const calendarEl = document.getElementById('calendar');
    if (!calendarEl) return;

    calendar = new FullCalendar.Calendar(calendarEl, {
        // Alap beallitasok
        initialView: 'dayGridMonth',
        locale: 'hu',
        firstDay: 1, // Hetfo
        height: '100%',
        editable: config.canEdit,
        selectable: config.canEdit,
        dayMaxEvents: 4,
        moreLinkText: 'tovabbi',

        // Fejlec gombok
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,dayGridWeek'
        },

        // Gombok szovege
        buttonText: {
            today: 'Ma',
            month: 'Honap',
            week: 'Het',
        },

        // Esemenyek betoltese
        events: function(fetchInfo, successCallback, failureCallback) {
            const storeId = getSelectedStoreId();
            if (!storeId) {
                successCallback([]);
                return;
            }

            fetch(config.baseUrl + '/schedule/api?store_id=' + storeId +
                  '&start=' + fetchInfo.startStr.substring(0, 10) +
                  '&end=' + fetchInfo.endStr.substring(0, 10))
                .then(function(response) {
                    if (!response.ok) throw new Error('HTTP ' + response.status);
                    return response.json();
                })
                .then(function(events) {
                    successCallback(events);
                    assignEmployeeColors(events);
                })
                .catch(function(error) {
                    console.error('Esemenyek betoltesi hiba:', error);
                    failureCallback(error);
                });
        },

        // Kattintas egy napra -> uj beosztas modal
        dateClick: function(info) {
            if (!config.canEdit) return;
            openScheduleModal(info.dateStr);
        },

        // Esemeny drag & drop (athelyezes)
        eventDrop: function(info) {
            const event = info.event;

            // Szabadsag nem mozgathato
            if (event.extendedProps.type === 'vacation') {
                info.revert();
                showNotification('A szabadsag esemenyek nem mozgathatoak.', 'warning');
                return;
            }

            const newDate = event.startStr.substring(0, 10);

            fetchWithCsrf(config.baseUrl + '/schedule/api/' + event.id + '/move', {
                method: 'POST',
                body: JSON.stringify({ new_date: newDate }),
            })
            .then(function(data) {
                if (!data.success) {
                    info.revert();
                    showNotification(data.error || 'Hiba tortent az athelyezes soran.', 'error');
                }
            })
            .catch(function(error) {
                info.revert();
                showNotification('Hiba tortent az athelyezes soran.', 'error');
                console.error('Move error:', error);
            });
        },

        // Kattintas egy esemenyre -> torles lehetoseg
        eventClick: function(info) {
            const event = info.event;

            // Szabadsag: csak info
            if (event.extendedProps.type === 'vacation') {
                showNotification(event.title, 'info');
                return;
            }

            if (!config.canEdit) return;

            // Torles megerosites
            if (confirm('Biztosan torolni szeretned ezt a beosztas bejegyzest?\n\n' + event.title)) {
                fetchWithCsrf(config.baseUrl + '/schedule/api/' + event.id + '/delete', {
                    method: 'POST',
                })
                .then(function(data) {
                    if (data.success) {
                        event.remove();
                        showNotification('Beosztas torolve.', 'success');
                    } else {
                        showNotification(data.error || 'Hiba tortent a torles soran.', 'error');
                    }
                })
                .catch(function(error) {
                    showNotification('Hiba tortent a torles soran.', 'error');
                    console.error('Delete error:', error);
                });
            }
        },

        // Esemeny rendereles: tooltip
        eventDidMount: function(info) {
            const props = info.event.extendedProps;
            if (props.type === 'schedule' && props.shift_start && props.shift_end) {
                info.el.title = info.event.title + '\nMuszak: ' +
                    props.shift_start.substring(0, 5) + ' - ' + props.shift_end.substring(0, 5);
            } else if (props.type === 'vacation') {
                info.el.title = info.event.title;
                info.el.style.cursor = 'default';
            }
        },
    });

    calendar.render();

    // Bolt valaszto figyeles
    const storeSelector = document.getElementById('store-selector');
    if (storeSelector && storeSelector.tagName === 'SELECT') {
        storeSelector.addEventListener('change', function() {
            // Dolgozok frissitese az uj bolthoz
            refreshEmployeeList(this.value);
            // Naptar esemenyek ujratoltese
            calendar.refetchEvents();
        });
    }
}

/**
 * Kivalasztott bolt ID lekerese
 */
function getSelectedStoreId() {
    const selector = document.getElementById('store-selector');
    if (!selector) return calendarConfig.storeId;
    return parseInt(selector.value) || calendarConfig.storeId;
}

/**
 * Beosztas modal megnyitasa
 */
function openScheduleModal(dateStr) {
    const modal = document.getElementById('schedule-modal');
    const dateInput = document.getElementById('modal-work-date');
    const dateDisplay = document.getElementById('modal-date-display');

    dateInput.value = dateStr;

    // Magyar datum formatum
    const d = new Date(dateStr + 'T12:00:00');
    const days = ['Vasarnap', 'Hetfo', 'Kedd', 'Szerda', 'Csutortok', 'Pentek', 'Szombat'];
    dateDisplay.value = d.getFullYear() + '. ' +
        String(d.getMonth() + 1).padStart(2, '0') + '. ' +
        String(d.getDate()).padStart(2, '0') + '. (' + days[d.getDay()] + ')';

    modal.classList.remove('hidden');
}

/**
 * Beosztas modal bezarasa
 */
function closeScheduleModal() {
    document.getElementById('schedule-modal').classList.add('hidden');
    document.getElementById('schedule-form').reset();
}

/**
 * Beosztas mentese (modal form submit)
 */
function submitSchedule(event) {
    event.preventDefault();

    const employeeId = document.getElementById('modal-employee').value;
    const workDate = document.getElementById('modal-work-date').value;
    const shiftStart = document.getElementById('modal-shift-start').value;
    const shiftEnd = document.getElementById('modal-shift-end').value;
    const storeId = getSelectedStoreId();

    if (!employeeId) {
        showNotification('Kerlek valassz dolgozot!', 'warning');
        return false;
    }

    if (!workDate) {
        showNotification('Hianyzo datum!', 'warning');
        return false;
    }

    fetchWithCsrf(calendarConfig.baseUrl + '/schedule/api', {
        method: 'POST',
        body: JSON.stringify({
            employee_id: parseInt(employeeId),
            store_id: parseInt(storeId),
            work_date: workDate,
            shift_start: shiftStart,
            shift_end: shiftEnd,
        }),
    })
    .then(function(data) {
        if (data.success) {
            closeScheduleModal();
            calendar.refetchEvents();
            showNotification('Beosztas sikeresen hozzaadva.', 'success');
        } else {
            showNotification(data.error || 'Hiba tortent a mentes soran.', 'error');
        }
    })
    .catch(function(error) {
        showNotification('Hiba tortent a mentes soran.', 'error');
        console.error('Store error:', error);
    });

    return false;
}

/**
 * Dolgozok listajanak frissitese bolt valtas eseten
 */
function refreshEmployeeList(storeId) {
    const list = document.getElementById('employee-list');
    const modalSelect = document.getElementById('modal-employee');

    // Dolgozok lekerese az API-bol (egyszerusitett: oldal ujratoltes helyett)
    // Mivel nincs kulon employee API, a naptar esemenyek alapjan frissitjuk,
    // vagy az oldalt ujratoltjuk bolt valtas eseten
    // Egyelore oldal ujratoltes bolt valtaskor
    if (calendarConfig.isOwner) {
        window.location.href = calendarConfig.baseUrl + '/schedule?store_id=' + storeId;
    }
}

/**
 * Dolgozo szinek hozzarendelese a sidebar elemekhez
 */
function assignEmployeeColors(events) {
    const colorMap = {};

    events.forEach(function(evt) {
        if (evt.extendedProps && evt.extendedProps.type === 'schedule' && evt.extendedProps.employee_id) {
            colorMap[evt.extendedProps.employee_id] = evt.backgroundColor;
        }
    });

    // Sidebar dolgozok szineinek beallitasa
    const colorPalette = [
        '#3B82F6', '#10B981', '#8B5CF6', '#F59E0B', '#EC4899',
        '#06B6D4', '#84CC16', '#F97316', '#6366F1', '#14B8A6',
        '#E11D48', '#7C3AED', '#0EA5E9', '#65A30D', '#D946EF',
    ];
    let colorIdx = 0;

    document.querySelectorAll('.employee-item').forEach(function(item) {
        const empId = parseInt(item.dataset.employeeId);
        let color = colorMap[empId];
        if (!color) {
            color = colorPalette[colorIdx % colorPalette.length];
            colorIdx++;
        }
        const dot = item.querySelector('.employee-color');
        if (dot) {
            dot.style.backgroundColor = color;
        }
    });
}

/**
 * Ertesites megjelenitese (toast)
 */
function showNotification(message, type) {
    // Ha van mar flash uzenet rendszer, hasznaljuk azt
    const container = document.querySelector('main') || document.body;

    const colors = {
        success: 'bg-green-100 text-green-800 border-green-300',
        error: 'bg-red-100 text-red-800 border-red-300',
        warning: 'bg-yellow-100 text-yellow-800 border-yellow-300',
        info: 'bg-blue-100 text-blue-800 border-blue-300',
    };

    const toast = document.createElement('div');
    toast.className = 'fixed top-20 right-4 z-[100] px-4 py-3 rounded-lg border shadow-lg text-sm font-medium transition-all ' +
        (colors[type] || colors.info);
    toast.textContent = message;
    document.body.appendChild(toast);

    setTimeout(function() {
        toast.style.opacity = '0';
        toast.style.transform = 'translateY(-10px)';
        setTimeout(function() { toast.remove(); }, 300);
    }, 4000);
}
