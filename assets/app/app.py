import os
import sys

# Adiciona o diretório raiz do projeto (Z:\) ao sys.path
sys.path.append(os.path.abspath(os.path.join(os.path.dirname(__file__), "../../")))


from flask import Flask
from views.financeiro import financeiro_bp

app = Flask(__name__)

# Rota padrão para teste (Hello Flask)
@app.route('/')
def index():
    return "Hello Flask!"

# Registrar o blueprint do financeiro
app.register_blueprint(financeiro_bp)

if __name__ == '__main__':
    app.run(host='0.0.0.0', port=8080, debug=True)

