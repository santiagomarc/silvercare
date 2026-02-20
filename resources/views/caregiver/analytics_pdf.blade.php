<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Health Report - {{ $elderlyUser->name ?? 'Patient' }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            font-size: 12px;
            color: #1f2937;
            line-height: 1.5;
            padding: 40px;
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            border-bottom: 3px solid #000080;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .logo {
            font-size: 28px;
            font-weight: 900;
            color: #1f2937;
        }
        .logo span {
            color: #000080;
        }
        .report-meta {
            text-align: right;
            font-size: 11px;
            color: #6b7280;
        }
        .report-meta h2 {
            font-size: 16px;
            color: #1f2937;
            margin-bottom: 5px;
        }
        .patient-info {
            background: #f3f4f6;
            border-radius: 8px;
            padding: 15px 20px;
            margin-bottom: 25px;
        }
        .patient-info h3 {
            font-size: 18px;
            margin-bottom: 5px;
        }
        .patient-info p {
            color: #6b7280;
            font-size: 11px;
        }
        .section {
            margin-bottom: 25px;
        }
        .section-title {
            font-size: 14px;
            font-weight: 800;
            color: #000080;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 2px solid #e5e7eb;
            padding-bottom: 8px;
            margin-bottom: 15px;
        }
        .health-score-box {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            margin-bottom: 25px;
        }
        .health-score-box.good { background: linear-gradient(135deg, #3b82f6, #2563eb); }
        .health-score-box.fair { background: linear-gradient(135deg, #f59e0b, #d97706); }
        .health-score-box.poor { background: linear-gradient(135deg, #ef4444, #dc2626); }
        .health-score-value {
            font-size: 48px;
            font-weight: 900;
        }
        .health-score-label {
            font-size: 16px;
            font-weight: 600;
            opacity: 0.9;
        }
        .stats-grid {
            display: table;
            width: 100%;
            margin-bottom: 20px;
        }
        .stats-grid .stat {
            display: table-cell;
            width: 25%;
            text-align: center;
            padding: 15px;
            background: #f9fafb;
            border: 1px solid #e5e7eb;
        }
        .stats-grid .stat:first-child {
            border-radius: 8px 0 0 8px;
        }
        .stats-grid .stat:last-child {
            border-radius: 0 8px 8px 0;
        }
        .stat-value {
            font-size: 24px;
            font-weight: 900;
            color: #1f2937;
        }
        .stat-label {
            font-size: 10px;
            color: #6b7280;
            text-transform: uppercase;
            font-weight: 600;
        }
        table.data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        table.data-table th {
            background: #f3f4f6;
            padding: 10px 12px;
            text-align: left;
            font-size: 10px;
            text-transform: uppercase;
            font-weight: 700;
            color: #6b7280;
            border-bottom: 2px solid #e5e7eb;
        }
        table.data-table td {
            padding: 10px 12px;
            border-bottom: 1px solid #e5e7eb;
        }
        table.data-table tr:last-child td {
            border-bottom: none;
        }
        .badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 10px;
            font-weight: 700;
        }
        .badge-green { background: #d1fae5; color: #065f46; }
        .badge-yellow { background: #fef3c7; color: #92400e; }
        .badge-red { background: #fee2e2; color: #991b1b; }
        .badge-blue { background: #dbeafe; color: #1e40af; }
        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
            text-align: center;
            font-size: 10px;
            color: #9ca3af;
        }
        .two-col {
            display: table;
            width: 100%;
        }
        .two-col .col {
            display: table-cell;
            width: 48%;
            vertical-align: top;
        }
        .two-col .col:first-child {
            padding-right: 2%;
        }
        .two-col .col:last-child {
            padding-left: 2%;
        }
    </style>
</head>
<body>

    <!-- Header -->
    <div class="header">
        <div class="logo">SILVER<span>CARE</span></div>
        <div class="report-meta">
            <h2>Health Analytics Report</h2>
            <p>Generated: {{ now()->format('F j, Y \a\t g:i A') }}</p>
            <p>Report Period: Last 7 Days</p>
        </div>
    </div>

    <!-- Patient Info -->
    <div class="patient-info">
        <h3>{{ $elderlyUser->name ?? 'Patient' }}</h3>
        <p>
            @if($elderly->date_of_birth)
                Age: {{ \Carbon\Carbon::parse($elderly->date_of_birth)->age }} years |
            @endif
            Email: {{ $elderlyUser->email ?? 'N/A' }}
        </p>
    </div>

    <!-- Health Score -->
    <div class="health-score-box {{ $healthScore >= 90 ? '' : ($healthScore >= 75 ? 'good' : ($healthScore >= 60 ? 'fair' : 'poor')) }}">
        <div class="health-score-value">{{ $healthScore }}</div>
        <div class="health-score-label">{{ $healthLabel }} Health Score</div>
    </div>

    <!-- Quick Stats -->
    <div class="stats-grid">
        <div class="stat">
            <div class="stat-value">{{ $totalReadings }}</div>
            <div class="stat-label">Total Readings</div>
        </div>
        <div class="stat">
            <div class="stat-value">{{ $readingsThisWeek }}</div>
            <div class="stat-label">This Week</div>
        </div>
        <div class="stat">
            <div class="stat-value">{{ $medicationSummary['adherenceRate'] ?? '—' }}%</div>
            <div class="stat-label">Med Adherence</div>
        </div>
        <div class="stat">
            <div class="stat-value">{{ $taskSummary['completionRate'] ?? '—' }}%</div>
            <div class="stat-label">Task Completion</div>
        </div>
    </div>

    <!-- Vitals Summary -->
    <div class="section">
        <h3 class="section-title">Vitals Summary (Last 7 Days)</h3>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Vital Type</th>
                    <th>Average</th>
                    <th>Min</th>
                    <th>Max</th>
                    <th>Readings</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach($analyticsData as $type => $data)
                    @if(($data['7days']['count'] ?? 0) > 0)
                    <tr>
                        <td><strong>{{ $data['config']['icon'] }} {{ $data['config']['name'] }}</strong></td>
                        @if($type === 'blood_pressure')
                            <td>{{ $data['7days']['systolic_avg'] ?? '-' }}/{{ $data['7days']['diastolic_avg'] ?? '-' }} {{ $data['config']['unit'] }}</td>
                            <td>{{ $data['7days']['systolic_min'] ?? '-' }}/{{ $data['7days']['diastolic_min'] ?? '-' }}</td>
                            <td>{{ $data['7days']['systolic_max'] ?? '-' }}/{{ $data['7days']['diastolic_max'] ?? '-' }}</td>
                        @else
                            <td>{{ $data['7days']['avg'] ?? '-' }} {{ $data['config']['unit'] }}</td>
                            <td>{{ $data['7days']['min'] ?? '-' }}</td>
                            <td>{{ $data['7days']['max'] ?? '-' }}</td>
                        @endif
                        <td>{{ $data['7days']['count'] }}</td>
                        <td>
                            @if(isset($healthFactors[$type]))
                                <span class="badge {{ $healthFactors[$type]['score'] >= 80 ? 'badge-green' : ($healthFactors[$type]['score'] >= 60 ? 'badge-yellow' : 'badge-red') }}">
                                    {{ $healthFactors[$type]['status'] }}
                                </span>
                            @else
                                <span class="badge badge-blue">Recorded</span>
                            @endif
                        </td>
                    </tr>
                    @endif
                @endforeach
            </tbody>
        </table>
    </div>

    <!-- Two Column Layout for Medications and Tasks -->
    <div class="two-col">
        <!-- Medication Summary -->
        <div class="col">
            <div class="section">
                <h3 class="section-title">Medication Summary</h3>
                @if($medicationSummary['totalMedications'] > 0)
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Medication</th>
                                <th>Taken / Scheduled</th>
                                <th>Adherence</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($medicationSummary['medications'] as $med)
                            <tr>
                                <td>{{ $med['name'] }}</td>
                                <td>{{ $med['taken'] }} / {{ $med['scheduled'] }}</td>
                                <td>
                                    <span class="badge {{ ($med['adherence'] ?? 0) >= 80 ? 'badge-green' : (($med['adherence'] ?? 0) >= 50 ? 'badge-yellow' : 'badge-red') }}">
                                        {{ $med['adherence'] ?? 0 }}%
                                    </span>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                    @if($medicationSummary['lowStockCount'] > 0)
                        <p style="color: #dc2626; font-weight: 600; margin-top: 10px;">
                            ⚠️ {{ $medicationSummary['lowStockCount'] }} medication(s) running low on stock
                        </p>
                    @endif
                @else
                    <p style="color: #6b7280;">No active medications</p>
                @endif
            </div>
        </div>

        <!-- Task Summary -->
        <div class="col">
            <div class="section">
                <h3 class="section-title">Task Summary</h3>
                @if($taskSummary['total'] > 0)
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Metric</th>
                                <th>Value</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>Completed Tasks</td>
                                <td><strong>{{ $taskSummary['completed'] }} / {{ $taskSummary['total'] }}</strong></td>
                            </tr>
                            <tr>
                                <td>Completion Rate</td>
                                <td>
                                    <span class="badge {{ ($taskSummary['completionRate'] ?? 0) >= 80 ? 'badge-green' : (($taskSummary['completionRate'] ?? 0) >= 50 ? 'badge-yellow' : 'badge-red') }}">
                                        {{ $taskSummary['completionRate'] ?? 0 }}%
                                    </span>
                                </td>
                            </tr>
                            @if($taskSummary['overdue'] > 0)
                            <tr>
                                <td>Overdue</td>
                                <td><span class="badge badge-red">{{ $taskSummary['overdue'] }} task(s)</span></td>
                            </tr>
                            @endif
                        </tbody>
                    </table>
                @else
                    <p style="color: #6b7280;">No tasks this week</p>
                @endif
            </div>
        </div>
    </div>

    <!-- Footer -->
    <div class="footer">
        <p>This report was generated by SilverCare Health Monitoring System</p>
        <p>For questions or concerns, please contact your healthcare provider.</p>
    </div>

</body>
</html>
