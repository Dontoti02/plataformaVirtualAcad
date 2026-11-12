body {
    font-family: DejaVu Sans, sans-serif;
    color: #1f2933;
}
.header {
    position: fixed;
    inset-block-start: -1.9cm;
    inset-inline: 0;
    height: 58px;
}

.header-bar {
    width: 100%;
    height: 58px;
    position: relative;
    border-bottom: 2px solid #1f3864;
}

.header-name {
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    width: 80%;
    margin: 0;
    font-size: 9.5pt;
    font-weight: bold;
    color: #1f3864;
    letter-spacing: 0.02em;
}

.header-logo {
    position: absolute;
    right: 0;
    top: 4px;
    height: 46px;
}

.title {
    margin: 0 0 8px;
    padding: 6px 0;
    background-color: #1f3864;
    color: #fff;
    font-size: 12pt;
    text-align: center;
    letter-spacing: 0.04em;
}
.meta {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 10px;
}

.meta th,
.meta td {
    border: 1px solid #8ea9db;
    padding: 3px 6px;
    font-size: 7.5pt;
    text-align: left;
}

.meta th {
    width: 13%;
    background-color: #d9e2f3;
    color: #1f3864;
    text-transform: uppercase;
    letter-spacing: 0.03em;
}

.meta td {
    width: 37%;
}

.section-title {
    margin: 0 0 5px;
    padding: 4px 8px;
    background-color: #d9e2f3;
    border-left: 4px solid #1f3864;
    color: #1f3864;
    font-size: 9pt;
}

.grid {
    width: 100%;
    table-layout: fixed;
    border-collapse: collapse;
}

.grid th,
.grid td {
    border: 1px solid #8ea9db;
    padding: 2px 1px;
    text-align: center;
    vertical-align: middle;
    word-wrap: break-word;
}
.grid.md th, .grid.md td { font-size: 7pt; }
.grid.sm th, .grid.sm td { font-size: 6pt; }
.grid.xs th, .grid.xs td { font-size: 5pt; }
.grid.xxs th, .grid.xxs td { font-size: 4.2pt; padding: 1px 0; }

.grid .lvl-capacity {
    background-color: #1f3864;
    color: #fff;
    font-weight: bold;
}

.grid .lvl-fixed {
    background-color: #1f3864;
    color: #fff;
    font-weight: bold;
}

.grid .lvl-indicator {
    background-color: #2f5597;
    color: #fff;
}

.grid .lvl-week {
    background-color: #d9e2f3;
    color: #1f3864;
}

.grid .lvl-type {
    background-color: #eef3fb;
    color: #44546a;
}

.grid .achievement {
    background-color: #fff2cc;
    font-weight: bold;
}

.grid .final {
    background-color: #e2efda;
    font-weight: bold;
}

.grid .student {
    text-align: left;
    padding-left: 4px;
}

.grid .striped td {
    background-color: #f7f9fc;
}

.grid .striped td.achievement {
    background-color: #fdecc4;
}

.grid .striped td.final {
    background-color: #d7e9c9;
}

.section {
    page-break-before: always;
}

.section-first {
    page-break-before: avoid;
}

.legend {
    margin-top: 8px;
    font-size: 6.5pt;
    color: #44546a;
}

.legend p {
    margin: 1px 0;
}

.legend .indent {
    padding-left: 14px;
}

.signatures {
    width: 100%;
    margin-top: 34px;
    border-collapse: collapse;
}

.signatures td {
    width: 33%;
    padding-top: 4px;
    border-top: 1px solid #1f2933;
    font-size: 7.5pt;
    text-align: center;
}

.signatures .spacer {
    border: none;
    width: 4%;
}
