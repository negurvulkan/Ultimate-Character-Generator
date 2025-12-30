<?php
// Public generator UI calling DB-backed API
?><!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Prozeduraler Körper-Generator</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <style>
        body { background-color: #f4f4f9; }
        .container { max-width: 960px; }
        .row-section { background-color: #eef2f5 !important; font-weight: bold; }
        .row-highlight { background-color: #e8f8f5; font-weight: 600; }
        .row-clothing { background-color: #f0fdf4; color: #1e7e34; }
        .row-puberty { background-color: #fff9db; font-style: italic; }
        table { width: 100%; }
    </style>
</head>
<body>
<div class="container my-4">
    <h1 class="text-center">Charakter-Maße Generator <small class="text-muted">(DB-first)</small></h1>
    <div class="card mb-3">
        <div class="card-body d-flex gap-3 flex-wrap align-items-end">
            <div>
                <label class="form-label">Geschlecht</label>
                <select id="gender" class="form-select">
                    <option value="male">Männlich</option>
                    <option value="female">Weiblich</option>
                </select>
            </div>
            <div>
                <label class="form-label">Alter</label>
                <input type="number" id="age" class="form-control" min="1" max="90" value="25">
            </div>
            <div>
                <label class="form-label">Seed (optional)</label>
                <input type="number" id="seed" class="form-control" placeholder="RNG Seed">
            </div>
            <div>
                <button class="btn btn-primary" onclick="runGenerator()">Generieren</button>
            </div>
        </div>
    </div>
    <div id="result"></div>
</div>
<script>
async function runGenerator() {
    const params = new URLSearchParams();
    params.append('gender', document.getElementById('gender').value);
    params.append('age', document.getElementById('age').value);
    const seedVal = document.getElementById('seed').value;
    if(seedVal) params.append('seed', seedVal);
    const res = await fetch('/api/generate.php', {method:'POST', body: params});
    const data = await res.json();
    render(data);
}

function render(data) {
    const resEl = document.getElementById('result');
    const row = (label, val, css = "") => `<tr><td class="${css}">${label}</td><td>${val ?? ''}</td></tr>`;
    let html = `<table class="table table-hover">`;
    html += row('Name', `<strong>${data.meta.name}</strong>`);
    html += row('Alter', `${data.meta.age} Jahre`);
    html += row('Körpertyp', `${data.meta.build} (Faktor ${data.meta.buildFactor})`);
    html += row('Größe / Gewicht', `${data.body.height} cm / ${data.body.weight} kg`);
    html += row('BMI', data.body.bmi);
    html += row('Haarfarbe', data.meta.hairColor);
    html += row('Augenfarbe', data.meta.eyeColor);
    html += row('Tanner', `${data.meta.tanner.summary || ''} P:${data.meta.tanner.primary} / H:${data.meta.tanner.pubic}`);
    html += row('Kopf & Gesicht', '', 'row-section');
    html += row('Kopfhöhe', `${data.body.headHeight} cm`);
    html += row('Kopf-Proportion', `1 zu ${data.body.headsTall}`);
    html += row('Kopfumfang', `${data.body.headCircum} cm`, 'row-highlight');
    html += row('Gesicht (LxB)', `${data.face.length} x ${data.face.width} cm`);
    html += row('Stirn (HxB)', `${data.face.foreheadH} x ${data.face.foreheadW} cm`);
    html += row('Augenabstand', `${data.face.eyeDist} cm`);
    html += row('Nase (LxB)', `${data.face.noseLen} x ${data.face.noseWidth} cm`);
    html += row('Mundbreite', `${data.face.mouthWidth} cm`);
    html += row('Ohr (L / Abstehend)', `${data.face.earLen} cm / ${data.face.earProt} cm`);
    html += row('Halslänge', `${data.body.neckLen} cm`);

    html += row('Torso & Oberkörper', '', 'row-section');
    html += row('Brustumfang', `${data.measurements.chest} cm`);
    if(data.measurements.underbust) html += row('Unterbrust', `${data.measurements.underbust} cm`);
    if(data.measurements.nippleDist) html += row('Brustwarzenabstand', `${data.measurements.nippleDist} cm`, 'row-highlight');
    html += row('Brustkorbtiefe', `${data.measurements.chestDepth} cm`);
    html += row('Schulterbreite', `${data.measurements.shoulderWidth} cm`, 'row-highlight');
    html += row('Armlänge', `${data.measurements.armLength} cm`);
    html += row('Taille', `${data.measurements.waist} cm`);
    html += row('Hüfte', `${data.measurements.hips} cm`);
    html += row('Gesäßumfang', `${data.measurements.gluteCircum} cm`);

    html += row('Hände & Arme', '', 'row-section');
    html += row('Handlänge', `${data.limbs.handLen} cm`);
    html += row('Handbreite', `${data.limbs.handW} cm`);
    html += row('Fingerlänge', `${data.limbs.fingerLen} cm`);

    html += row('Beine & Füße', '', 'row-section');
    html += row('Beinlänge gesamt', `${data.legs.total} cm`);
    html += row('Inseam', `${data.legs.inseam} cm`, 'row-highlight');
    html += row('Oberschenkel (L/U)', `${data.legs.thighLen} cm / ${data.legs.thighCircum} cm`);
    html += row('Wadenumfang', `${data.legs.calfCircum} cm`);
    html += row('Knöchelumfang', `${data.legs.ankleCircum} cm`);
    html += row('Fußlänge', `${data.limbs.footLen} cm`);

    html += row('Kleidung', '', 'row-section');
    if(data.clothing.pants) html += row('Hose', data.clothing.pants, 'row-clothing');
    if(data.clothing.shirt) html += row('Oberteil', data.clothing.shirt, 'row-clothing');
    if(data.clothing.dress) html += row('Kleid', data.clothing.dress, 'row-clothing');
    if(data.clothing.bra) html += row('BH', data.clothing.bra, 'row-clothing');

    html += '</table>';
    resEl.innerHTML = html;
}

runGenerator();
</script>
</body>
</html>
