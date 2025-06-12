document.addEventListener('DOMContentLoaded', function() {
    console.log("calendar.js loaded and DOM is ready!");

    const calendarEl = document.getElementById('calendar'); // The main calendar container
    const calendarHeaderEl = document.getElementById('calendar-header'); // For month/year and navigation
    const calendarGridEl = document.getElementById('calendar-grid'); // For the actual day grid
    const timeSlotsContainer = document.getElementById('time-slots-container'); // For displaying time slots
    const confirmBookingBtn = document.getElementById('confirmBookingBtn'); // The booking button
    const bookingDateInput = document.getElementById('booking_date'); // Hidden input for date
    const startTimeInput = document.getElementById('start_time');     // Hidden input for start time
    const endTimeInput = document.getElementById('end_time');       // Hidden input for end time


    const room_id = new URLSearchParams(window.location.search).get('room_id'); // Get room_id from URL

    let currentMonth = new Date().getMonth();
    let currentYear = new Date().getFullYear();

    // Function to render the calendar for a given month and year
    function renderCalendar(month, year) {
        calendarHeaderEl.innerHTML = ''; // Clear previous header
        calendarGridEl.innerHTML = '';   // Clear previous grid

        const monthNames = ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"];
        const daysInMonth = new Date(year, month + 1, 0).getDate();
        const firstDayOfMonth = new Date(year, month, 1).getDay(); // 0 for Sunday, 1 for Monday

        // --- Render Header ---
        const headerHtml = `
            <button id="prevMonth" style="padding: 8px 15px; background-color: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer;">&lt; Prev</button>
            <h2 style="margin: 0; color: #333;">${monthNames[month]} ${year}</h2>
            <button id="nextMonth" style="padding: 8px 15px; background-color: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer;">Next &gt;</button>
        `;
        calendarHeaderEl.insertAdjacentHTML('afterbegin', headerHtml);


        // --- Render Days of Week ---
        const daysOfWeek = ["Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat"];
        daysOfWeek.forEach(day => {
            calendarGridEl.insertAdjacentHTML('beforeend', `<div class="calendar-day-header">${day}</div>`);
        });

        // Add empty divs for the first day of the month alignment
        for (let i = 0; i < firstDayOfMonth; i++) {
            calendarGridEl.insertAdjacentHTML('beforeend', `<div class="calendar-day"></div>`);
        }

        // --- Render Days of Month ---
        for (let day = 1; day <= daysInMonth; day++) {
            // Format fullDate as YYYY-MM-DD (e.g., 2025-06-08)
            const fullDate = `${year}-${String(month + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
            const dayHtml = `<div class="calendar-day current-month" data-date="${fullDate}">${day}</div>`;
            calendarGridEl.insertAdjacentHTML('beforeend', dayHtml);
        }

        // --- Add Event Listeners for Navigation Buttons ---
        document.getElementById('prevMonth').addEventListener('click', () => {
            currentMonth--;
            if (currentMonth < 0) {
                currentMonth = 11;
                currentYear--;
            }
            renderCalendar(currentMonth, currentYear);
        });

        document.getElementById('nextMonth').addEventListener('click', () => {
            currentMonth++;
            if (currentMonth > 11) {
                currentMonth = 0;
                currentYear++;
            }
            renderCalendar(currentMonth, currentYear);
        });

        // --- Add Event Listeners for Day Clicks ---
        calendarGridEl.querySelectorAll('.calendar-day.current-month').forEach(dayEl => {
            dayEl.addEventListener('click', (event) => {
                // Remove 'selected' class from all days first
                calendarGridEl.querySelectorAll('.calendar-day.selected').forEach(selectedDay => {
                    selectedDay.classList.remove('selected');
                });
                // Add 'selected' class to the clicked day
                event.currentTarget.classList.add('selected');

                const selectedDate = event.currentTarget.dataset.date;
                console.log('Selected Date:', selectedDate);

                // Update the hidden form field for the booking date
                bookingDateInput.value = selectedDate;

                // Disable the booking button until a time slot is selected
                confirmBookingBtn.disabled = true;
                startTimeInput.value = '';
                endTimeInput.value = '';

                // Fetch and display time slots for this selectedDate
                fetchTimeSlots(selectedDate);
            });
        });

        // --- Auto-select today's date initially if present in calendar ---
        const today = new Date();
        // Check if the current month being rendered is actually the current month
        if (month === today.getMonth() && year === today.getFullYear()) {
            const todayFormatted = `${today.getFullYear()}-${String(today.getMonth() + 1).padStart(2, '0')}-${String(today.getDate()).padStart(2, '0')}`;
            const todayEl = calendarGridEl.querySelector(`.calendar-day[data-date="${todayFormatted}"]`);
            if (todayEl) {
                todayEl.classList.add('selected');
                bookingDateInput.value = todayFormatted; // Set initial form date
                fetchTimeSlots(todayFormatted); // Fetch slots for today immediately
            }
        } else {
             // If navigating to a different month, ensure time slots are cleared
             timeSlotsContainer.innerHTML = '<p style="text-align: center; grid-column: 1 / -1;">Please select a date on the calendar.</p>';
             confirmBookingBtn.disabled = true;
             bookingDateInput.value = '';
             startTimeInput.value = '';
             endTimeInput.value = '';
        }
    }


    // --- Function to Fetch and Display Time Slots (AJAX) ---
    function fetchTimeSlots(date) {
        if (!room_id) {
            console.error("Room ID is not available for fetching time slots.");
            timeSlotsContainer.innerHTML = '<p style="color: red; text-align: center;">Error: Room ID missing.</p>';
            return;
        }

        timeSlotsContainer.innerHTML = '<p style="text-align: center; grid-column: 1 / -1;">Loading time slots...</p>'; // Loading message
        confirmBookingBtn.disabled = true; // Disable button while loading slots

        fetch(`get_time_slots.php?room_id=${room_id}&date=${date}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok ' + response.statusText);
                }
                return response.json();
            })
            .then(data => {
                timeSlotsContainer.innerHTML = ''; // Clear loading message

                if (data.error) {
                    timeSlotsContainer.innerHTML = `<p style="color: red; text-align: center;">Error: ${data.error}</p>`;
                    return;
                }
                console.log("time slots ?")

                if (data.length > 0) {
                    data.forEach(slot => {
                        const slotDiv = document.createElement('div');
                        slotDiv.classList.add('time-slot');
                        slotDiv.textContent = `${slot.start_time} - ${slot.end_time}`;

                        if (slot.is_booked) {
                            slotDiv.classList.add('booked');
                            slotDiv.title = 'Booked';
                        } else {
                            slotDiv.classList.add('available');
                            slotDiv.dataset.startTime = slot.start_time;
                            slotDiv.dataset.endTime = slot.end_time;
                            slotDiv.addEventListener('click', (event) => {
                                // Remove 'selected-slot' from others
                                timeSlotsContainer.querySelectorAll('.selected-slot').forEach(s => s.classList.remove('selected-slot'));
                                // Add 'selected-slot' to clicked
                                event.currentTarget.classList.add('selected-slot');

                                // Update the hidden form fields
                                startTimeInput.value = slot.start_time;
                                endTimeInput.value = slot.end_time;

                                // Enable the booking button
                                confirmBookingBtn.disabled = false;
                            });
                        }
                        timeSlotsContainer.appendChild(slotDiv);
                    });
                } else {
                    timeSlotsContainer.innerHTML = '<p style="text-align: center; grid-column: 1 / -1;">No time slots available for this date. Try another day.</p>';
                }
            })
            .catch(error => {
                console.error('Error fetching time slots:', error);
                timeSlotsContainer.innerHTML = '<p style="color: red; text-align: center;">Failed to load time slots. Please try again.</p>';
                confirmBookingBtn.disabled = true;
            });
    }


    // Initial render of the calendar when the page loads
    renderCalendar(currentMonth, currentYear);
});