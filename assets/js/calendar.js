document.addEventListener('DOMContentLoaded', () => {
    if (!document.getElementById('calendar-main')) return;

    const waitForAppState = setInterval(() => {
        if (window.appState && window.appState.isLoggedIn && window.appState.user) {
            clearInterval(waitForAppState);
            initializeCalendar();
        }
    }, 100);

    // fungsi untuk menginisialisasi kalender ni wak
    function initializeCalendar() {
        window.addEventListener('openEventModal', (e) => {
            const { eventId } = e.detail;
            openEventModal({ id: eventId });
        });

        const getResponsiveView = () => {
            if (window.innerWidth < 640) {
                return 'listWeek';
            }
            return 'timeGridWeek';
        };

        const calendarEl = document.getElementById('calendar-main');
        const searchInput = document.getElementById('search-input');
        const noResultsMessage = document.getElementById('search-no-results-message');
        const miniCalendarEl = document.getElementById('mini-calendar');
        const currentDateTitle = document.getElementById('current-date-title');
        const prevBtn = document.getElementById('prev-btn');
        const nextBtn = document.getElementById('next-btn');
        const todayBtn = document.getElementById('today-btn');
        const viewSwitcher = document.getElementById('view-switcher');
        const createEventBtn = document.getElementById('create-event-sidebar-btn');
        const myCalendarsList = document.getElementById('my-calendars-list');
        const createCalendarBtn = document.getElementById('create-calendar-btn');
        const eventModal = document.getElementById('event-modal');
        const eventModalBox = document.getElementById('event-modal-box');
        const eventForm = document.getElementById('event-form');
        const modalTitle = document.getElementById('modal-title');
        const eventModalContent = document.getElementById('event-modal-content');
        const eventModalFooter = document.getElementById('event-modal-footer');
        const calendarModal = document.getElementById('calendar-modal');
        const calendarModalBox = document.getElementById('calendar-modal-box');
        const calendarForm = document.getElementById('calendar-form');
        const calendarModalTitle = document.getElementById('calendar-modal-title');

        const state = { calendars: [], currentEventParticipants: [] };

        const calendar = new FullCalendar.Calendar(calendarEl, {
            timeZone: 'local',
            headerToolbar: false,
            initialView: getResponsiveView(),
            locale: 'id',
            firstDay: 1,
            height: '100%',
            expandRows: true,
            editable: true,
            selectable: true,

            events: (fetchInfo, successCallback, failureCallback) => {
                const visibleIds = getVisibleCalendarIds();
                let endpoint = `events.php?visible_calendars=${visibleIds.join(',')}`;

                apiCall(endpoint, 'GET')
                    .then(events => {
                        const processedEvents = events.map(event => ({
                            ...event,
                            start: event.start ? event.start.replace(' ', 'T') + 'Z' : null,
                            end: event.end ? event.end.replace(' ', 'T') + 'Z' : null,
                        }));
                        successCallback(processedEvents);
                    })
                    .catch(failureCallback);
            },
            select: handleDateSelect,
            eventClick: (clickInfo) => openEventModal(clickInfo.event),
            eventDrop: handleEventDropOrResize,
            eventResize: handleEventDropOrResize,
            datesSet: (dateInfo) => {
                if (miniCalendar) miniCalendar.gotoDate(dateInfo.start);
                updateHeader();
            }
        });

        const miniCalendar = new FullCalendar.Calendar(miniCalendarEl, {
            timeZone: 'local',
            initialView: 'dayGridMonth',
            locale: 'id',
            firstDay: 1,
            headerToolbar: { start: 'title', center: '', end: 'prev,next' },
            dateClick: (info) => calendar.gotoDate(info.date)
        });

        calendar.render();
        miniCalendar.render();
        lucide.createIcons();

        // Fungsi untuk mengubah format tanggal ke UTC ni wak
        function formatToUTC(dateInput) {
            if (!dateInput) return '';
            const date = new Date(dateInput);
            return date.toISOString().slice(0, 19).replace('T', ' ');
        }

        // Fungsi untuk mengubah format tanggal untuk input datetime-local ni wak
        function formatForDateTimePicker(dateString) {
            if (!dateString) return '';
            const safeDateString = dateString.includes('T') ? dateString : dateString.replace(' ', 'T') + 'Z';
            const date = new Date(safeDateString);
            if (isNaN(date.getTime())) {
                return '';
            }
            const year = date.getFullYear();
            const month = (date.getMonth() + 1).toString().padStart(2, '0');
            const day = date.getDate().toString().padStart(2, '0');
            const hours = date.getHours().toString().padStart(2, '0');
            const minutes = date.getMinutes().toString().padStart(2, '0');
            return `${year}-${month}-${day}T${hours}:${minutes}`;
        }

        // Fungsi untuk memperbarui judul kalender dan tombol tampilan ni wak
        function updateHeader() {
            currentDateTitle.textContent = calendar.view.title;
            viewSwitcher.querySelectorAll('button').forEach(btn => {
                btn.classList.remove('bg-white', 'dark:bg-blue-900', 'shadow');
                if(btn.dataset.view === calendar.view.type) {
                    btn.classList.add('bg-white', 'dark:bg-blue-900', 'shadow');
                }
            });
        }

        prevBtn.addEventListener('click', () => calendar.prev());
        nextBtn.addEventListener('click', () => calendar.next());
        todayBtn.addEventListener('click', () => calendar.today());
        viewSwitcher.addEventListener('click', (e) => {
            const button = e.target.closest('button');
            if (button && button.dataset.view) calendar.changeView(button.dataset.view);
        });

        // Fungsi untuk menangani pencarian global ni wak
        async function handleGlobalSearch() {
            const searchTerm = searchInput.value.trim();
            if (!searchTerm) {
                hideNoResultsMessage();
                calendar.refetchEvents();
                return;
            }
            try {
                const results = await apiCall(`events.php?action=search&q=${encodeURIComponent(searchTerm)}`);
                if (results.length > 0) {
                    hideNoResultsMessage();
                    const targetDate = new Date(results[0].start.replace(' ', 'T') + 'Z');
                    calendar.gotoDate(targetDate);
                } else {
                    showNoResultsMessage();
                }
            } catch (error) {
                alert(`Terjadi kesalahan saat mencari: ${error.message}`);
            }
        }

        function showNoResultsMessage() { noResultsMessage.classList.remove('hidden'); }
        function hideNoResultsMessage() { noResultsMessage.classList.add('hidden'); }

        searchInput.addEventListener('input', debounce(handleGlobalSearch, 500));

        // Fungsi untuk membuka modal ni wak
        function openModal(modal, modalBox) {
            modal.classList.remove('hidden');
            setTimeout(() => modalBox.classList.remove('opacity-0', 'scale-95'), 10);
        }

        // Fungsi untuk menutup modal ni wak
        function closeModal(modal, modalBox) {
            modalBox.classList.add('opacity-0', 'scale-95');
            setTimeout(() => modal.classList.add('hidden'), 300);
        }

        // Fungsi untuk mengambil dan merender daftar kalender ni wak
        async function fetchAndRenderCalendars() {
            try {
                const data = await apiCall('calendars.php', 'GET');
                state.calendars = data.records || [];
                renderCalendarsList();
                calendar.refetchEvents();
            } catch (error) {
                myCalendarsList.innerHTML = `<div class="text-xs text-red-500">Gagal memuat kalender.</div>`;
            }
        }

        function renderCalendarsList() {
            myCalendarsList.innerHTML = '';
            if (state.calendars.length === 0) return;
            state.calendars.forEach(cal => {
                const isOwner = cal.permission_level === 'owner';
                const calEl = document.createElement('div');
                calEl.className = 'flex items-center justify-between group text-sm p-1 rounded-md hover:bg-[var(--color-bg-secondary)]';
                calEl.innerHTML = `
                    <div class="flex items-center flex-grow overflow-hidden">
                        <input type="checkbox" data-calendar-id="${cal.id}" class="calendar-toggle h-4 w-4 rounded border-gray-300 mr-3" style="accent-color:${cal.color};" checked>
                        <span class="truncate">${cal.name}</span>
                        ${!isOwner ? '<i data-lucide="users" class="h-3 w-3 ml-2 text-[var(--color-text-muted)] flex-shrink-0" title="Dibagikan"></i>' : ''}
                    </div>
                    <div class="items-center space-x-2 hidden group-hover:flex">
                        ${isOwner ? `<button class="edit-calendar-btn" data-id="${cal.id}"><i data-lucide="pencil" class="h-4 w-4 text-[var(--color-text-muted)] hover:text-blue-500 pointer-events-none"></i></button>` : ''}
                        ${isOwner && !cal.is_default ? `<button class="delete-calendar-btn" data-id="${cal.id}"><i data-lucide="trash-2" class="h-4 w-4 text-[var(--color-text-muted)] hover:text-red-500 pointer-events-none"></i></button>` : ''}
                    </div>`;
                myCalendarsList.appendChild(calEl);
            });
            lucide.createIcons();
        }

        // Fungsi untuk mendapatkan daftar ID kalender yang terlihat ni wak
        function getVisibleCalendarIds() {
            return Array.from(myCalendarsList.querySelectorAll('.calendar-toggle:checked')).map(cb => cb.dataset.calendarId);
        }

        createCalendarBtn.addEventListener('click', () => openCalendarModal());

        // Fungsi untuk membuka modal kalender ni wak
        function openCalendarModal(calendarData = null) {
            calendarForm.reset();
            document.getElementById('calendar_color').value = '#3b82f6';
            if (calendarData) {
                calendarModalTitle.textContent = 'Edit Kalender';
                document.getElementById('calendar_id_field').value = calendarData.id;
                document.getElementById('calendar_name').value = calendarData.name;
                document.getElementById('calendar_color').value = calendarData.color;
            } else {
                calendarModalTitle.textContent = 'Buat Kalender Baru';
                document.getElementById('calendar_id_field').value = '';
            }
            openModal(calendarModal, calendarModalBox);
        }
        
        calendarForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const id = document.getElementById('calendar_id_field').value;
            const payload = {
                id: id ? parseInt(id) : null,
                name: document.getElementById('calendar_name').value,
                color: document.getElementById('calendar_color').value,
            };
            try {
                await apiCall('calendars.php', id ? 'PUT' : 'POST', payload);
                closeModal(calendarModal, calendarModalBox);
                fetchAndRenderCalendars();
            } catch (error) { alert(`Error: ${error.message}`); }
        });

        myCalendarsList.addEventListener('click', (e) => {
            if (e.target.classList.contains('calendar-toggle')) {
                calendar.refetchEvents();
                return;
            }
            const editBtn = e.target.closest('.edit-calendar-btn');
            const deleteBtn = e.target.closest('.delete-calendar-btn');
            if (editBtn) openCalendarModal(state.calendars.find(c => c.id == editBtn.dataset.id));
            if (deleteBtn && confirm('Yakin ingin menghapus kalender ini? Seluruh acara di dalamnya juga akan terhapus.')) {
                handleCalendarDelete(deleteBtn.dataset.id);
            }
        });

        // Fungsi untuk menghapus kalender ni wak
        async function handleCalendarDelete(id) {
             try {
                await apiCall('calendars.php', 'DELETE', { id: parseInt(id) });
                fetchAndRenderCalendars();
            } catch (error) { alert(`Error: ${error.message}`); }
        }

        function handleDateSelect(selectInfo) { openEventModal(null, selectInfo); }

        // Fungsi untuk menangani perubahan drag dan resize acara ni wak
        async function handleEventDropOrResize({ event }) {
            try {
                const eventDetails = await apiCall(`events.php?event_id=${event.id}`, 'GET');
                const payload = {
                    id: parseInt(event.id),
                    title: event.title,
                    start: formatToUTC(event.start),
                    end: formatToUTC(event.end || event.start),
                    allDay: event.allDay,
                    calendar_id: eventDetails.calendar_id,
                    description: eventDetails.description,
                    location: eventDetails.location,
                    participants: eventDetails.participants.map(p => p.id),
                    reminder_minutes: parseInt(eventDetails.reminder_minutes)
                };
                await apiCall('events.php', 'PUT', payload);
            } catch (error) {
                alert(`Gagal menyimpan perubahan: ${error.message}`);
                event.revert();
            }
        }

        // Fungsi untuk merender daftar peserta acara ni wak
        function renderEventForm(eventData, isOwner, isParticipant, participantStatus) {
            const canEdit = isOwner;
            const isInvited = isParticipant && participantStatus === 'pending';

            const writableCalendars = state.calendars.filter(cal => ['owner', 'can_edit', 'can_edit_and_share'].includes(cal.permission_level));
            const calendarOptions = writableCalendars.map(cal => `<option value="${cal.id}" ${cal.id == eventData.calendar_id ? 'selected' : ''}>${cal.name}</option>`).join('');

            eventModalContent.innerHTML = `
                <div class="space-y-4">
                    <div><label class="block text-sm font-medium">Judul Acara</label><input type="text" name="title" id="title" required class="input-field mt-1" value="${eventData.title || ''}" ${!canEdit ? 'disabled' : ''}></div>
                    <div><label class="block text-sm font-medium">Kalender</label><select name="calendar_id" id="calendar_id" class="input-field mt-1" ${!canEdit ? 'disabled' : ''}>${calendarOptions}</select></div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div><label class="block text-sm font-medium">Mulai</label><input type="datetime-local" name="start" id="start_time" required class="input-field mt-1" value="${formatForDateTimePicker(eventData.start_time)}" ${!canEdit ? 'disabled' : ''}></div>
                        <div><label class="block text-sm font-medium">Selesai</label><input type="datetime-local" name="end" id="end_time" required class="input-field mt-1" value="${formatForDateTimePicker(eventData.end_time)}" ${!canEdit ? 'disabled' : ''}></div>
                    </div>
                    <div><label class="block text-sm font-medium">Pengingat</label><select name="reminder_minutes" id="reminder_minutes" class="input-field mt-1" ${!canEdit ? 'disabled' : ''}>...</select></div>
                    <div><label class="block text-sm font-medium">Deskripsi</label><textarea name="description" id="description" rows="3" class="input-field mt-1" ${!canEdit ? 'disabled' : ''}>${eventData.description || ''}</textarea></div>
                    <div><label class="block text-sm font-medium">Lokasi</label><input type="text" name="location" id="location" class="input-field mt-1" value="${eventData.location || ''}" ${!canEdit ? 'disabled' : ''}></div>
                    <div class="border-t border-[var(--color-border-primary)] pt-4">
                        <label class="block text-sm font-medium">Peserta (${eventData.participants ? eventData.participants.length + 1 : 1})</label>
                        <div id="participants-list" class="mt-2 flex flex-wrap gap-2"></div>
                        ${canEdit ? `<div class="relative mt-2"><input type="search" id="participant-search" placeholder="Cari untuk mengundang..." autocomplete="off" class="input-field"><div id="participant-search-results" class="absolute w-full mt-1 bg-[var(--color-bg-primary)] border border-[var(--color-border-primary)] rounded-md shadow-lg z-20 hidden"></div></div>` : ''}
                    </div>
                </div>`;

            const reminderSelect = document.getElementById('reminder_minutes');
            const reminderOptions = { '0': 'Tanpa pengingat', '15': '15 menit', '30': '30 menit', '60': '1 jam' };
            reminderSelect.innerHTML = Object.entries(reminderOptions).map(([val, text]) => `<option value="${val}" ${val == eventData.reminder_minutes ? 'selected' : ''}>${text}</option>`).join('');

            renderParticipantsList(eventData.creator_name, eventData.participants);

            if (canEdit) {
                document.getElementById('participant-search').addEventListener('keyup', debounce(handleParticipantSearch, 300));
            }

            eventModalFooter.innerHTML = '';
            if (isInvited) {
                eventModalFooter.innerHTML = `
                    <div class="w-full flex justify-end space-x-3">
                        <button type="button" id="decline-invitation-btn" class="px-4 py-2 text-sm font-medium rounded-md bg-red-500/20 text-red-500 hover:bg-red-500/30">Tolak</button>
                        <button type="button" id="accept-invitation-btn" class="px-4 py-2 text-sm font-medium rounded-md bg-green-500/20 text-green-500 hover:bg-green-500/30">Terima</button>
                    </div>`;
                document.getElementById('accept-invitation-btn').onclick = () => handleInvitationResponse('accepted');
                document.getElementById('decline-invitation-btn').onclick = () => handleInvitationResponse('declined');
            } else if (canEdit) {
                eventModalFooter.innerHTML = `
                    <button type="button" id="delete-event-btn" class="text-[var(--color-text-danger)] hover:opacity-80 font-semibold"><i data-lucide="trash-2" class="inline h-4 w-4 mr-1"></i>Hapus</button>
                    <button type="submit" class="bg-[var(--color-accent-primary)] hover:bg-[var(--color-accent-primary-hover)] text-[var(--color-text-on-accent)] font-bold py-2 px-4 rounded-md transition-colors">Simpan</button>`;
                document.getElementById('delete-event-btn').onclick = handleDeleteEvent;
                lucide.createIcons();
            }
        }

        // Fungsi untuk membuka modal acara ni wak
        async function openEventModal(event = null, selectInfo = null) {
            eventForm.reset();
            state.currentEventParticipants = [];
            
            if (event) {
                try {
                    const fullEventData = await apiCall(`events.php?event_id=${event.id}`, 'GET');
                    state.currentEventParticipants = fullEventData.participants || [];
                    document.getElementById('event_id').value = fullEventData.id;

                    const currentUser = window.appState.user;
                    const isOwner = fullEventData.user_id == currentUser.id;
                    const participantInfo = fullEventData.participants.find(p => p.id == currentUser.id);
                    const isParticipant = !!participantInfo;
                    const participantStatus = isParticipant ? participantInfo.status : null;

                    modalTitle.textContent = isOwner ? 'Edit Acara' : 'Detail Acara';
                    renderEventForm(fullEventData, isOwner, isParticipant, participantStatus);

                } catch (error) {
                    alert(`Gagal memuat detail acara: ${error.message}`);
                    return;
                }
            } else {
                modalTitle.textContent = 'Buat Acara Baru';
                document.getElementById('event_id').value = '';

                const startDate = selectInfo ? selectInfo.start : new Date();
                let endDate = selectInfo ? selectInfo.end : new Date(new Date().getTime() + 60 * 60 * 1000);

                if (selectInfo && (endDate.getTime() - startDate.getTime()) < (30 * 60 * 1000)) {
                    endDate = new Date(startDate.getTime() + 60 * 60 * 1000);
                }

                const writableCalendars = state.calendars.filter(cal => ['owner', 'can_edit', 'can_edit_and_share'].includes(cal.permission_level));

                renderEventForm({
                    title: '',
                    start_time: startDate.toISOString(),
                    end_time: endDate.toISOString(),
                    calendar_id: writableCalendars.length > 0 ? writableCalendars[0].id : null,
                    reminder_minutes: 30,
                    description: '',
                    location: '',
                    participants: [],
                    creator_name: window.appState.user.full_name
                }, true, false, null);
            }
            openModal(eventModal, eventModalBox);
        }

        createEventBtn.addEventListener('click', () => openEventModal(null, { start: new Date(), end: new Date(Date.now() + 60*60*1000) }));

        eventForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(eventForm);
            const eventId = formData.get('event_id');
            const payload = {
                id: eventId ? parseInt(eventId) : null,
                title: formData.get('title'),
                start: formatToUTC(formData.get('start')),
                end: formatToUTC(formData.get('end')),
                calendar_id: parseInt(formData.get('calendar_id')),
                reminder_minutes: parseInt(formData.get('reminder_minutes')),
                description: formData.get('description'),
                location: formData.get('location'),
                allDay: false,
                participants: state.currentEventParticipants.map(p => p.id)
            };
            try {
                await apiCall('events.php', eventId ? 'PUT' : 'POST', payload);
                closeModal(eventModal, eventModalBox);
                calendar.refetchEvents();
            } catch (error) { alert(`Gagal menyimpan acara: ${error.message}`); }
        });

        // Fungsi untuk menghapus acara ni wak
        async function handleDeleteEvent() {
            const eventId = document.getElementById('event_id').value;
            if (confirm('Yakin ingin menghapus acara ini?')) {
                try {
                    await apiCall('events.php', 'DELETE', { id: parseInt(eventId) });
                    closeModal(eventModal, eventModalBox);
                    calendar.refetchEvents();
                } catch (error) { alert(`Gagal menghapus acara: ${error.message}`); }
            }
        }

        // Fungsi untuk menangani respons undangan ni wak
        async function handleInvitationResponse(status) {
            const eventId = document.getElementById('event_id').value;
            try {
                await apiCall('events.php', 'POST', {
                    action: 'respond_invitation',
                    event_id: parseInt(eventId),
                    status: status
                });
                closeModal(eventModal, eventModalBox);
                calendar.refetchEvents();
            } catch (error) {
                alert(`Gagal merespons undangan: ${error.message}`);
            }
        }

        // Fungsi untuk menangani pencarian peserta ni wak
        async function handleParticipantSearch(e) {
            const searchTerm = e.target.value;
            const resultsContainer = document.getElementById('participant-search-results');
            if (searchTerm.length < 2) {
                resultsContainer.classList.add('hidden');
                return;
            }
            try {
                const results = await apiCall(`users.php?search=${searchTerm}`, 'GET');
                resultsContainer.innerHTML = '';
                if (results.records && results.records.length > 0) {
                    results.records.forEach(user => {
                        if (state.currentEventParticipants.some(p => p.id === user.id)) return;
                        const userEl = document.createElement('div');
                        userEl.className = 'p-2 hover:bg-[var(--color-bg-secondary)] cursor-pointer';
                        userEl.textContent = `${user.full_name} (${user.email})`;
                        userEl.onclick = () => addParticipant(user);
                        resultsContainer.appendChild(userEl);
                    });
                } else {
                    resultsContainer.innerHTML = '<div class="p-2 text-[var(--color-text-muted)]">Pengguna tidak ditemukan.</div>';
                }
                resultsContainer.classList.remove('hidden');
            } catch (error) {
                console.error("Gagal mencari peserta:", error);
                let errorHtml = `<div class="p-2 text-red-500 font-semibold">Gagal memuat data.</div>`;
                if (error && error.error_details) {
                    errorHtml += `<div class="p-2 text-xs text-red-400 bg-red-900/50 rounded-md mt-1 font-mono"><p><strong>Pesan:</strong> ${error.error_details.message}</p><p><strong>File:</strong> ${error.error_details.file}</p><p><strong>Baris:</strong> ${error.error_details.line}</p></div>`;
                } else if (error && error.message) {
                    errorHtml += `<div class="p-2 text-xs text-red-400">${error.message}</div>`;
                }
                resultsContainer.innerHTML = errorHtml;
                resultsContainer.classList.remove('hidden');
            }
        }

        // Fungsi untuk menambahkan peserta lain ke acara ni wak
        function addParticipant(user) {
            if (!state.currentEventParticipants.some(p => p.id === user.id)) {
                state.currentEventParticipants.push(user);
                renderParticipantsList(window.appState.user.full_name, state.currentEventParticipants);
            }
            document.getElementById('participant-search').value = '';
            document.getElementById('participant-search-results').classList.add('hidden');
        }

        // Fungsi untuk menghapus peserta dari acara ni wak
        function removeParticipant(userId) {
            state.currentEventParticipants = state.currentEventParticipants.filter(p => p.id !== userId);
            renderParticipantsList(window.appState.user.full_name, state.currentEventParticipants);
        }

        // Fungsi untuk merender daftar peserta acara ni wak
        function renderParticipantsList(creatorName, participants = []) {
            const listContainer = document.getElementById('participants-list');
            listContainer.innerHTML = '';

            const creatorPill = document.createElement('div');
            creatorPill.className = 'flex items-center bg-gray-200 dark:bg-gray-600 text-sm font-medium px-2.5 py-0.5 rounded-full';
            creatorPill.innerHTML = `<i data-lucide="star" class="h-3 w-3 mr-1.5 text-yellow-500"></i><span>${creatorName} (Pembuat)</span>`;
            listContainer.appendChild(creatorPill);

            participants.forEach(user => {
                const statusIcons = {
                    pending: '<i data-lucide="clock" class="h-3 w-3 mr-1.5 text-gray-400" title="Menunggu Respons"></i>',
                    accepted: '<i data-lucide="check-circle" class="h-3 w-3 mr-1.5 text-green-500" title="Diterima"></i>',
                    declined: '<i data-lucide="x-circle" class="h-3 w-3 mr-1.5 text-red-500" title="Ditolak"></i>'
                };
                const pill = document.createElement('div');
                pill.className = 'flex items-center bg-gray-200 dark:bg-gray-600 text-sm font-medium px-2.5 py-0.5 rounded-full';
                pill.innerHTML = `${statusIcons[user.status] || ''}<span>${user.full_name}</span>
                    ${document.getElementById('participant-search') ? `<button type="button" class="ml-2 text-gray-500 hover:text-red-500 remove-participant-btn" data-id="${user.id}"><i data-lucide="x" class="h-4 w-4 pointer-events-none"></i></button>` : ''}`;
                listContainer.appendChild(pill);
            });
            listContainer.querySelectorAll('.remove-participant-btn').forEach(btn => btn.onclick = () => removeParticipant(parseInt(btn.dataset.id)));
            lucide.createIcons();
        }

        fetchAndRenderCalendars();

        [eventModal, calendarModal].forEach(modal => {
            const modalBox = modal.querySelector('.transform');
            modal.addEventListener('click', (e) => { if (e.target === modal) closeModal(modal, modalBox); });
            modal.querySelectorAll('.close-modal-btn').forEach(btn => btn.addEventListener('click', () => closeModal(modal, modalBox)));
        });
        document.addEventListener('keydown', (e) => {
            if (e.key === "Escape") {
                if (!eventModal.classList.contains('hidden')) closeModal(eventModal, eventModalBox);
                if (!calendarModal.classList.contains('hidden')) closeModal(calendarModal, calendarModalBox);
            }
        });

        // Fungsi debounce untuk mengurangi frekuensi panggilan fungsi ni wak
        function debounce(func, delay) {
            let timeout;
            return function(...args) {
                clearTimeout(timeout);
                timeout = setTimeout(() => func.apply(this, args), delay);
            };
        }

        let resizeTimeout;
        window.addEventListener('resize', () => {
            clearTimeout(resizeTimeout);
            resizeTimeout = setTimeout(() => {
                const newView = getResponsiveView();
                if (calendar.view.type !== newView) {
                    calendar.changeView(newView);
                }
            }, 250);
        });
    }
});
