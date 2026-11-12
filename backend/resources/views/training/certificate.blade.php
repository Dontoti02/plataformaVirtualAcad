<!DOCTYPE html>
<html>

<head>
    <style>
        @page {
            margin: 0;
            size: landscape;
        }

        body {
            margin: 0;
            padding: 2.5cm;
            box-sizing: border-box;
        }

        .background {
            position: fixed;
            z-index: -1;
            width: 100%;
            height: 100%;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            text-align: center;
            vertical-align: middle;
            word-wrap: break-word;
        }

        .rowName {
            font-family: 'Arial', sans-serif;
            font-size: 20px;
        }

        .rowTitle {
            font-family: 'Arial', sans-serif;
            font-size: 50px;
            margin-left: 20%;
            margin-right: 20%;
        }

        .rowStudent {
            font-family: 'Arial', sans-serif;
            font-size: 30px;
            margin-top: 50px;
            margin-left: 10%;
            margin-right: 10%;
        }

        .lineName {
            border-bottom: 2px solid black;
            width: 80%;
            margin: 0 auto;
        }

        .rowMessage {
            font-family: 'Arial', sans-serif;
            font-size: 18px;
            margin-top: 20px;
            margin-left: 20%;
            margin-right: 20%;
        }

        .rowLogo {
            position: relative;
            width: 15%;
        }

        .logo {
            position: absolute;
            margin-top: 30px;
            max-width: 100%;
            max-height: 100%;
        }

        .rowBlank {
            height: 150px;
        }

        .lineSignatures {
            border-bottom: 2px solid black;
            width: 80%;
            margin: 0 auto;
        }

        .rowSignatures {
            font-family: 'Arial', sans-serif;
            font-size: 20px;
        }

        .rowProfile {
            font-family: 'Arial', sans-serif;
            font-size: 18px;
        }

        .rowAddress {
            font-family: 'Arial', sans-serif;
            font-size: 18px;
        }

        .rowDate {
            font-family: 'Arial', sans-serif;
            font-size: 18px;
        }
    </style>

</head>

<body>
    <img src="data:image/{{ mime_content_type(storage_path("app/$background")) }};base64,{{ base64_encode(file_get_contents(storage_path("app/$background"))) }}"
        class="background">

    <table>
        <tbody>
            <tr>
                <td colspan="3">
                    <div class="rowName">{{ $institutionName }}</div>
                </td>
            </tr>
            <tr>
                <td colspan="3">
                    <div class="rowTitle">{{ $title }}</div>
                </td>
            </tr>
            <tr>
                <td colspan="3">
                    <div class="rowStudent">{{ $student }}</div>
                </td>
            </tr>
            <tr>
                <td colspan="3">
                    <div class="lineName"></div>
                </td>
            </tr>
            <tr>
                <td colspan="3">
                    <div class="rowMessage">
                        Este diploma certifica que este alumno ha concluido sus estudios universitarios en
                        {{ $institutionName }}
                    </div>
                </td>
            </tr>
            <tr>
                <td>
                    <div class="rowBlank"></div>
                </td>
                <td rowspan="4" class="rowLogo">
                    <img src="data:image/{{ mime_content_type(storage_path("app/$institutionLogo")) }};base64,{{ base64_encode(file_get_contents(storage_path("app/$institutionLogo"))) }}"
                        class="logo">
                </td>
                <td>
                    <div class="rowBlank"></div>
                </td>
            </tr>
            <tr>
                <td>
                    <div class="lineSignatures"></div>
                </td>
                <td>
                    <div class="lineSignatures"></div>
                </td>
            </tr>
            <tr>
                <td>
                    <div class="rowSignatures">{{ $director }}</div>
                </td>
                <td>
                    <div class="rowSignatures">{{ $secretary }}</div>
                </td>
            </tr>
            <tr>
                <td>
                    <div class="rowProfile">Director</div>
                </td>
                <td>
                    <div class="rowProfile">Secretario</div>
                </td>
            </tr>
            <tr>
                <td colspan="3">
                    <div class="rowAddress">{{ $address }}</div>
                </td>
            </tr>
            <tr>
                <td colspan="3">
                    <div class="rowDate">{{ $date }}</div>
                </td>
            </tr>
        </tbody>
    </table>
</body>

</html>
