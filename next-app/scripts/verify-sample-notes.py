import json
import urllib.request
import urllib.parse

API_KEY = 'AIzaSyDZyyejjFwpWn6uZdlQpW8xO_o6vtCL4rE'
EMAIL = 'seed.user.1779040198@example.com'
PASSWORD = 'Seed1234!'

sign_in_url = f'https://identitytoolkit.googleapis.com/v1/accounts:signInWithPassword?key={API_KEY}'
payload = json.dumps({'email': EMAIL, 'password': PASSWORD, 'returnSecureToken': True}).encode('utf-8')
req = urllib.request.Request(sign_in_url, data=payload, headers={'Content-Type': 'application/json'})
with urllib.request.urlopen(req) as r:
    result = json.loads(r.read().decode('utf-8'))

id_token = result['idToken']
print('Signed in as', EMAIL)

url = f'https://noteshare-3-default-rtdb.firebaseio.com/notes.json?auth={urllib.parse.quote(id_token)}'
req = urllib.request.Request(url, headers={'Content-Type': 'application/json'})
with urllib.request.urlopen(req) as r:
    notes = json.loads(r.read().decode('utf-8'))

print('got notes', len(notes), list(notes.keys())[:10])
print(notes)
