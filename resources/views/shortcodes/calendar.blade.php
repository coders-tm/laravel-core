<div class="section-content">
    <div id="calendar"></div>
</div>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        var calendar = new FullCalendar.Calendar(document.getElementById('calendar'), {
            height: 'auto',
            themeSystem: 'bootstrap',
            firstDay: '1',
            timeZone: 'Europe/London',
            initialView: 'listWeek',
            headerToolbar: {
                start: 'title',
                center: '',
                end: 'today prev next'
            },
            buttonIcons: {
                close: 'fa-times',
                prev: 'fa-angle-left',
                next: 'fa-angle-right'
            },
            titleFormat: {
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            },
            dayMaxEvents: true,
            events: {
                url: "{{ $endpoint }}",
                extraParams: {
                    hasClass: true
                },
                startParam: 'startOfWeek',
                endParam: 'endOfWeek',
            },
            eventTimeFormat: {
                hour: 'numeric',
                minute: '2-digit',
            },
        });

        calendar.render();
    });
</script>
