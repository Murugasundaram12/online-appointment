document.addEventListener("DOMContentLoaded", function () {
    const toggle = document.querySelector("[data-sidebar-toggle]");
    if (toggle) {
        toggle.addEventListener("click", () => document.body.classList.toggle("sidebar-open"));
    }

    document.addEventListener("click", (event) => {
        if (document.body.classList.contains("sidebar-open") && event.target === document.body) {
            document.body.classList.remove("sidebar-open");
        }
    });

    document.querySelectorAll("form").forEach((form) => {
        form.addEventListener("submit", () => {
            const submitter = form.querySelector('button[type="submit"], button:not([type])');
            if (submitter && !submitter.dataset.keepEnabled) {
                submitter.classList.add("disabled");
            }
        });
    });

    const ctxTrend = document.getElementById("bookingTrendChart");
    if (ctxTrend && window.Chart) {
        new Chart(ctxTrend.getContext("2d"), {
            type: "bar",
            data: window.dashboardAppointmentChart || { labels: [], datasets: [] },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: "top", align: "end", labels: { usePointStyle: true, boxWidth: 8 } }
                },
                scales: {
                    y: { beginAtZero: true, grid: { color: "#eef2f7" } },
                    x: { grid: { display: false } }
                }
            }
        });
    }

    const ctxChannel = document.getElementById("bookingChannelChart");
    if (ctxChannel && window.Chart) {
        new Chart(ctxChannel.getContext("2d"), {
            type: "doughnut",
            data: window.dashboardStatusChart || { labels: [], datasets: [] },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: "72%",
                plugins: { legend: { display: false } }
            }
        });
    }
});
