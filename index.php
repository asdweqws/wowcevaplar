<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WOW Çözücü</title>
    
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="WOW Çözücü">
    <meta name="theme-color" content="#4f46e5">
    <link rel="apple-touch-icon" href="https://cdn-icons-png.flaticon.com/512/3565/3565418.png">

    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; }
        body { background: #f4f7f6; display: flex; flex-direction: column; align-items: center; min-height: 100vh; padding: 20px 15px; }
        
        .search-card { background: #ffffff; padding: 20px; border-radius: 20px; box-shadow: 0 4px 15px rgba(0,0,0,0.06); width: 100%; max-width: 450px; text-align: center; }
        .search-card h2 { color: #1f2937; margin-bottom: 15px; font-size: 22px; }
        
        .input-group { display: flex; gap: 8px; }
        .input-group input { flex: 1; padding: 14px; border: 2px solid #e5e7eb; border-radius: 12px; font-size: 18px; outline: none; transition: 0.2s; text-align: center; }
        .input-group input:focus { border-color: #4f46e5; }
        .input-group button { padding: 14px 20px; background: #4f46e5; color: white; border: none; border-radius: 12px; font-weight: bold; font-size: 16px; cursor: pointer; }
        .input-group button:active { background: #4338ca; transform: scale(0.98); }
        
        #loading { display: none; margin-top: 15px; font-weight: 600; color: #4f46e5; font-size: 15px; }
        #error { display: none; margin-top: 15px; color: #ef4444; font-weight: 600; font-size: 14px; }
        
        .board-container { margin-top: 25px; display: flex; flex-direction: column; align-items: center; gap: 20px; width: 100%; }
        
        .crossword-grid { display: grid; gap: 6px; padding: 10px; background: #ffffff; border-radius: 16px; box-shadow: 0 4px 15px rgba(0,0,0,0.04); }
        
        .cell { width: 44px; height: 44px; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 22px; font-weight: 800; text-transform: uppercase; }
        .cell.filled { background: #fffdf5; color: #451a03; border: 2px solid #fde68a; box-shadow: 0 2px 4px rgba(0,0,0,0.05); }
        .cell.empty { background: transparent; }
        
        .words-badge-container { display: flex; flex-wrap: wrap; gap: 8px; justify-content: center; max-width: 450px; }
        .word-badge { background: #e0e7ff; color: #3730a3; font-weight: 700; padding: 8px 14px; border-radius: 16px; font-size: 15px; }
    </style>
</head>
<body>

    <div class="search-card">
        <h2>🧩 WOW Çözücü</h2>
        <div class="input-group">
            <input type="text" id="bolumInput" placeholder="Harfleri Girin" autocomplete="off" />
            <button onclick="sorgula()">Bul</button>
        </div>
        <div id="loading">Kelime aranıyor...</div>
        <div id="error"></div>
    </div>

    <div class="board-container" id="boardContainer" style="display: none;">
        <div class="crossword-grid" id="gridDisplay"></div>
        <div class="words-badge-container" id="wordsDisplay"></div>
    </div>

    <script>
        async function sorgula() {
            const bolum = document.getElementById('bolumInput').value.trim();
            const loading = document.getElementById('loading');
            const error = document.getElementById('error');
            const boardContainer = document.getElementById('boardContainer');
            const gridDisplay = document.getElementById('gridDisplay');
            const wordsDisplay = document.getElementById('wordsDisplay');

            if (!bolum) return;

            loading.style.display = 'block';
            error.style.display = 'none';
            boardContainer.style.display = 'none';
            gridDisplay.innerHTML = '';
            wordsDisplay.innerHTML = '';

            try {
                const res = await fetch(`api.php?bolum=${encodeURIComponent(bolum)}`);
                const data = await res.json();

                if (data.status !== 'success') {
                    throw new Error(data.message || 'Veri bulunamadı.');
                }

                const grid = data.crossword_grid;

                // Tablo matrisi var ise çizdir
                if (Array.isArray(grid) && grid.length > 0 && Array.isArray(grid[0])) {
                    const colCount = grid[0].length;
                    gridDisplay.style.gridTemplateColumns = `repeat(${colCount}, 44px)`;

                    grid.forEach(row => {
                        row.forEach(char => {
                            const cell = document.createElement('div');
                            if (char !== "") {
                                cell.className = 'cell filled';
                                cell.textContent = char;
                            } else {
                                cell.className = 'cell empty';
                            }
                            gridDisplay.appendChild(cell);
                        });
                    });
                }

                // Kelimeleri rozet olarak bas
                if (Array.isArray(data.kelimeler)) {
                    data.kelimeler.forEach(w => {
                        const badge = document.createElement('div');
                        badge.className = 'word-badge';
                        badge.textContent = w;
                        wordsDisplay.appendChild(badge);
                    });
                }

                boardContainer.style.display = 'flex';

            } catch (err) {
                error.textContent = err.message;
                error.style.display = 'block';
            } finally {
                loading.style.display = 'none';
            }
        }
    </script>
</body>
</html>
