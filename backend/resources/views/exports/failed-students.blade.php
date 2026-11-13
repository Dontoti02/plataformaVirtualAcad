<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>{{ $title }}</title>
    <style>
        @page {
            margin: 0.8cm 0.8cm 1.3cm 0.8cm;
            size: a4 landscape;
        }

        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 8pt;
            color: #1e293b;
            margin: 0;
            padding: 0;
        }

        .header-box {
            width: 100%;
            height: 58px;
            background-color: #0f172a;
            color: #ffffff;
            margin-bottom: 10px;
            padding: 8px 14px;
            box-sizing: border-box;
        }

        .header-table {
            width: 100%;
            border-collapse: collapse;
        }

        .header-title {
            font-size: 11pt;
            font-weight: bold;
            color: #ffffff;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .header-sub {
            font-size: 8pt;
            color: #cbd5e1;
        }

        .report-heading {
            text-align: center;
            font-size: 11.5pt;
            font-weight: bold;
            color: #0f172a;
            margin: 4px 0 8px 0;
            text-transform: uppercase;
        }

        .meta-container {
            width: 100%;
            margin-bottom: 8px;
        }

        .meta-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 8pt;
        }

        .meta-label {
            font-weight: bold;
            color: #475569;
            width: 16%;
            padding: 2px 4px;
        }

        .meta-val {
            color: #0f172a;
            width: 34%;
            padding: 2px 4px;
        }

        .kpi-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 6px 0;
            margin-bottom: 10px;
        }

        .kpi-cell {
            padding: 5px 8px;
            text-align: center;
            border: 1px solid #cbd5e1;
            background-color: #f8fafc;
            border-radius: 4px;
        }

        .kpi-title {
            font-size: 7pt;
            color: #64748b;
            text-transform: uppercase;
            font-weight: bold;
        }

        .kpi-number {
            font-size: 11pt;
            font-weight: bold;
            margin-top: 2px;
        }

        .kpi-danger {
            color: #b91c1c;
            background-color: #fef2f2;
            border-color: #fecaca;
        }

        .kpi-success {
            color: #15803d;
            background-color: #f0fdf4;
            border-color: #bbf7d0;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 7.5pt;
        }

        .data-table thead {
            display: table-header-group;
        }

        .data-table tr {
            page-break-inside: avoid;
        }

        .data-table th {
            background-color: #1e293b;
            color: #ffffff;
            font-weight: bold;
            text-align: left;
            padding: 5px 6px;
            border: 1px solid #0f172a;
            font-size: 7pt;
            text-transform: uppercase;
        }

        .data-table td {
            padding: 4px 6px;
            border: 1px solid #e2e8f0;
            color: #334155;
        }

        .data-table tr:nth-child(even) {
            background-color: #f8fafc;
        }

        .text-center {
            text-align: center;
        }

        .badge-danger {
            display: inline-block;
            padding: 2px 5px;
            background-color: #fee2e2;
            color: #991b1b;
            font-weight: bold;
            font-size: 6.5pt;
            border-radius: 3px;
        }

        .score-failed {
            color: #dc2626;
            font-weight: bold;
        }

        .footer {
            position: fixed;
            bottom: -0.7cm;
            left: 0;
            right: 0;
            font-size: 7pt;
            color: #94a3b8;
            border-top: 1px solid #e2e8f0;
            padding-top: 4px;
        }
    </style>
</head>

<body>
    <div class="footer">
        <table style="width: 100%;">
            <tr>
                <td style="text-align: left;">Reporte Académico MINEDU - Seguimiento y Planes de Recuperación Pedagógica</td>
                <td style="text-align: right;">Generado automáticamente por la Plataforma Virtual</td>
            </tr>
        </table>
    </div>

    <!-- Encabezado Institucional -->
    <div class="header-box">
        <table class="header-table">
            <tr>
                <td style="vertical-align: middle;">
                    <div class="header-title">{{ $institutionName }}</div>
                    <div class="header-sub">{{ $applicationName }} - Sistema de Gestión Académica</div>
                </td>
                <td style="text-align: right; vertical-align: middle;">
                    @if (!empty($institutionLogo))
                        @php
                            $logoPath = storage_path("app/{$institutionLogo}");
                        @endphp
                        @if (file_exists($logoPath))
                            <img src="data:image/{{ mime_content_type($logoPath) }};base64,{{ base64_encode(file_get_contents($logoPath)) }}"
                                style="max-height: 42px;" alt="Logo">
                        @endif
                    @endif
                </td>
            </tr>
        </table>
    </div>

    <div class="report-heading">{{ $title }}</div>

    <div class="meta-container">
        <table class="meta-table">
            <tr>
                <td class="meta-label">Periodo Académico:</td>
                <td class="meta-val">{{ $filters['Periodo'] ?? '-' }}</td>
                <td class="meta-label">Programa de Estudios:</td>
                <td class="meta-val">{{ $filters['Programa de Estudios'] ?? 'TODOS' }}</td>
            </tr>
            <tr>
                <td class="meta-label">Ciclo:</td>
                <td class="meta-val">{{ $filters['Ciclo'] ?? 'TODOS' }}</td>
                <td class="meta-label">Unidad Didáctica:</td>
                <td class="meta-val">{{ $filters['Unidad Didáctica'] ?? 'TODAS' }}</td>
            </tr>
            <tr>
                <td class="meta-label">Fecha de Emisión:</td>
                <td class="meta-val">{{ $date }}</td>
                @if (!empty($filters['Búsqueda']))
                    <td class="meta-label">Filtro Búsqueda:</td>
                    <td class="meta-val">{{ $filters['Búsqueda'] }}</td>
                @else
                    <td class="meta-label"></td>
                    <td class="meta-val"></td>
                @endif
            </tr>
        </table>
    </div>

    @if (!empty($summary))
        <table class="kpi-table">
            <tr>
                <td class="kpi-cell">
                    <div class="kpi-title">Total Evaluados</div>
                    <div class="kpi-number">{{ $summary['total_students'] ?? 0 }}</div>
                </td>
                <td class="kpi-cell kpi-success">
                    <div class="kpi-title">Estudiantes Aprobados</div>
                    <div class="kpi-number">{{ $summary['total_approved'] ?? 0 }}</div>
                </td>
                <td class="kpi-cell kpi-danger">
                    <div class="kpi-title">Estudiantes No Aprobados</div>
                    <div class="kpi-number">{{ $summary['total_failed'] ?? 0 }}</div>
                </td>
                <td class="kpi-cell kpi-danger">
                    <div class="kpi-title">Tasa de Desaprobación</div>
                    <div class="kpi-number">{{ $summary['failure_rate'] ?? 0 }}%</div>
                </td>
                <td class="kpi-cell">
                    <div class="kpi-title">Unidades Didácticas</div>
                    <div class="kpi-number">{{ $summary['total_classrooms'] ?? 0 }}</div>
                </td>
            </tr>
        </table>
    @endif

    <table class="data-table">
        <thead>
            <tr>
                <th class="text-center" style="width: 24px;">N°</th>
                <th style="width: 52px;">D.N.I.</th>
                <th style="width: 155px;">Apellidos y Nombres</th>
                <th style="width: 120px;">Programa de Estudios</th>
                <th class="text-center" style="width: 34px;">Ciclo</th>
                <th style="width: 70px;">Sec. / Turno</th>
                <th>Unidad Didáctica</th>
                <th style="width: 125px;">Docente a Cargo</th>
                <th class="text-center" style="width: 36px;">Nota</th>
                <th class="text-center" style="width: 64px;">Condición</th>
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $idx => $student)
                <tr>
                    <td class="text-center">{{ $idx + 1 }}</td>
                    <td>{{ $student['document_number'] ?? '-' }}</td>
                    <td style="font-weight: 500;">{{ $student['student_name'] ?? '-' }}</td>
                    <td>{{ $student['study_program_name'] ?? '-' }}</td>
                    <td class="text-center">{{ $student['cycle_name'] ?? '-' }}</td>
                    <td>{{ $student['section_shift'] ?? '-' }}</td>
                    <td>{{ $student['course_name'] ?? '-' }}</td>
                    <td>{{ $student['teacher_name'] ?? '-' }}</td>
                    <td class="text-center score-failed">{{ $student['score'] ?? '-' }}</td>
                    <td class="text-center">
                        <span class="badge-danger">DESAPROBADO</span>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="10" class="text-center" style="padding: 18px; color: #64748b;">
                        No se registraron estudiantes con notas desaprobatorias bajo los filtros seleccionados.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>

</html>
