from flask import Flask, request, jsonify
from flask_cors import CORS
import pandas as pd
from sklearn.preprocessing import LabelEncoder
from sklearn.feature_extraction.text import TfidfVectorizer
from sklearn.ensemble import RandomForestClassifier
import warnings
import os
warnings.filterwarnings("ignore")

app = Flask(__name__)
CORS(app)  # Permettre les requêtes CORS

# Variables globales pour le modèle
model = None
vectorizer = None
le = None
is_model_ready = False

def load_and_train_model():
    """Charger les données et entraîner le modèle"""
    global model, vectorizer, le, is_model_ready
    
    try:
        # Vérifier si le fichier existe
        if not os.path.exists("dataset.csv"):
            print("❌ ERREUR : dataset.csv introuvable dans le répertoire courant")
            print("📁 Répertoire courant :", os.getcwd())
            print("📋 Fichiers présents :", os.listdir('.'))
            return False
        
        print("📊 Chargement du dataset...")
        df = pd.read_csv("dataset.csv")
        print(f"✅ Dataset chargé : {len(df)} échantillons")
        
        # Vérifier les colonnes requises
        required_cols = ['Disease'] + [f"Symptom_{i}" for i in range(1, 18)]
        missing_cols = [col for col in required_cols if col not in df.columns]
        if missing_cols:
            print(f"❌ ERREUR : Colonnes manquantes dans le dataset : {missing_cols}")
            return False
        
        # Préparation des données
        symptom_cols = [f"Symptom_{i}" for i in range(1, 18)]
        for col in symptom_cols:
            df[col] = df[col].fillna("").astype(str).str.strip().str.lower()
        
        df["symptoms_text"] = df[symptom_cols].apply(
            lambda row: " ".join([s for s in row if s != ""]), axis=1
        )
        
        # Encodage et vectorisation
        print("🔄 Entraînement du modèle...")
        le = LabelEncoder()
        y = le.fit_transform(df["Disease"])
        
        vectorizer = TfidfVectorizer(max_features=1000, stop_words='english')
        X = vectorizer.fit_transform(df["symptoms_text"])
        
        # Entraînement du modèle
        model = RandomForestClassifier(n_estimators=200, random_state=42)
        model.fit(X, y)
        
        is_model_ready = True
        print("✅ Modèle entraîné avec succès !")
        print(f"🏥 Maladies reconnues : {list(le.classes_)}")
        return True
        
    except Exception as e:
        print(f"❌ ERREUR lors du chargement : {str(e)}")
        return False

@app.route('/')
def index():
    return jsonify({
        'status': 'API Flask active',
        'model_ready': is_model_ready,
        'message': 'API de prédiction de maladies'
    })

@app.route('/predict', methods=['POST', 'OPTIONS'])
def predict():
    # Gérer les requêtes OPTIONS (CORS preflight)
    if request.method == 'OPTIONS':
        return jsonify({'status': 'OK'})
    
    try:
        if not is_model_ready:
            return jsonify({
                'error': 'Modèle non disponible',
                'message': 'Le dataset n\'a pas pu être chargé'
            }), 500
        
        data = request.get_json()
        if not data or 'symptoms' not in data:
            return jsonify({
                'error': 'Données invalides',
                'message': 'Veuillez fournir une liste de symptômes'
            }), 400
        
        symptoms = data.get('symptoms', [])
        if not symptoms:
            return jsonify({
                'error': 'Aucun symptôme fourni',
                'message': 'Veuillez saisir au moins un symptôme'
            }), 400
        
        # Nettoyer et préparer les symptômes
        symptoms_clean = [s.lower().strip() for s in symptoms if s.strip() != ""]
        symptoms_text = ' '.join(symptoms_clean)
        
        print(f"🔍 Analyse des symptômes : {symptoms_clean}")
        
        # Prédiction
        X_new = vectorizer.transform([symptoms_text])
        prediction = model.predict(X_new)
        probabilities = model.predict_proba(X_new)[0]
        
        disease = le.inverse_transform(prediction)[0]
        confidence = max(probabilities) * 100
        
        print(f"📋 Prédiction : {disease} (confiance: {confidence:.1f}%)")
        
        return jsonify({
            'symptoms': symptoms_clean,
            'disease': disease,
            'confidence': round(confidence, 1),
            'status': 'success'
        })
        
    except Exception as e:
        print(f"❌ Erreur lors de la prédiction : {str(e)}")
        return jsonify({
            'error': 'Erreur de prédiction',
            'message': str(e)
        }), 500

@app.route('/health')
def health():
    return jsonify({
        'status': 'healthy',
        'model_ready': is_model_ready,
        'timestamp': pd.Timestamp.now().isoformat()
    })

if __name__ == '__main__':
    print("🚀 Démarrage de l'API Flask...")
    print("📁 Répertoire de travail :", os.getcwd())
    
    # Charger et entraîner le modèle au démarrage
    if load_and_train_model():
        print("🌐 Serveur prêt sur http://127.0.0.1:5001")
    else:
        print("⚠️  Serveur démarré mais modèle non disponible")
    
    app.run(debug=True, port=5001, host='127.0.0.1')
