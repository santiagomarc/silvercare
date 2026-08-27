/**
 * SilverCare Caregiver Live Realtime Sync (Laravel Reverb / WebSockets)
 * Listens on private-caregiver.{id} for critical alerts, doses, check-ins, and status updates.
 */

window.SilverCareRealtime = (function () {
    function init(caregiverProfileId) {
        if (!caregiverProfileId || typeof window.Echo === 'undefined') {
            console.log('Echo / Reverb not initialized or caregiver profile ID missing.');
            return;
        }

        const channel = window.Echo.private(`caregiver.${caregiverProfileId}`);

        // 1. Critical & Emergency Alert Fired
        channel.listen('.critical.alert.fired', function (data) {
            console.log('🚨 Realtime critical alert received:', data);
            showRealtimeToast(`🚨 ALERT: ${data.title} from ${data.patient_name}`, 'critical');
            prependAlertCard(data);
        });

        // 2. Dose Confirmed by Senior
        channel.listen('.dose.confirmed', function (data) {
            console.log('💊 Dose confirmed:', data);
            showRealtimeToast(`💊 ${data.patient_name} confirmed taking ${data.medication_name}.`, 'success');
        });

        // 3. Daily Check-in Received
        channel.listen('.checkin.received', function (data) {
            console.log('👋 Check-in received:', data);
            const msg = data.status === 'need_help'
                ? `⚠️ ${data.patient_name} checked in and requested assistance!`
                : `👋 ${data.patient_name} completed daily check-in (Doing OK).`;
            showRealtimeToast(msg, data.status === 'need_help' ? 'warning' : 'info');
        });

        // 4. Alert Status Updated (Acknowledge / Resolve)
        channel.listen('.alert.status.updated', function (data) {
            console.log('Alert status updated:', data);
            const card = document.getElementById(`alert-card-${data.alert_id}`);
            if (card && data.state === 'resolved') {
                card.remove();
            }
        });
    }

    function prependAlertCard(alert) {
        const container = document.getElementById('clinical-alert-center');
        if (!container) return;

        const cardHtml = `
            <div class="relative overflow-hidden rounded-2xl p-5 border shadow-md transition-all bg-red-500/10 border-red-500 text-red-950 dark:text-red-100 animate-bounce" id="alert-card-${alert.alert_id}">
                <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                    <div class="flex items-start gap-3.5">
                        <div class="w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0 text-xl bg-red-600 text-white animate-pulse">
                            🚨
                        </div>
                        <div>
                            <div class="flex items-center gap-2 flex-wrap">
                                <span class="text-xs font-black uppercase tracking-wider px-2 py-0.5 rounded-md bg-red-600 text-white">
                                    ${alert.severity.toUpperCase()}
                                </span>
                                <span class="text-xs font-semibold text-slate-500 dark:text-slate-400">Just now</span>
                            </div>
                            <h4 class="text-base font-extrabold mt-1 text-slate-900 dark:text-white">${alert.title}</h4>
                            <p class="text-sm font-medium text-slate-700 dark:text-slate-200 mt-0.5">${alert.message}</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2.5 flex-shrink-0 self-end sm:self-center">
                        <button onclick="acknowledgeAlert(${alert.alert_id})" class="px-4 py-2 bg-slate-900 hover:bg-slate-800 text-white text-xs font-bold rounded-xl shadow transition-colors">
                            Acknowledge
                        </button>
                    </div>
                </div>
            </div>
        `;

        container.insertAdjacentHTML('afterbegin', cardHtml);
    }

    function showRealtimeToast(message, type) {
        let toast = document.createElement('div');
        const bg = type === 'critical' ? 'bg-red-600' : (type === 'warning' ? 'bg-amber-600' : (type === 'success' ? 'bg-emerald-600' : 'bg-slate-900'));
        toast.className = `fixed top-6 right-6 z-50 px-5 py-3 rounded-2xl text-white text-xs font-black shadow-2xl ${bg} animate-fade-in max-w-sm flex items-center gap-2 border-2 border-white`;
        toast.textContent = message;
        document.body.appendChild(toast);

        setTimeout(() => {
            if (toast) toast.remove();
        }, 5000);
    }

    return {
        init: init,
    };
})();
