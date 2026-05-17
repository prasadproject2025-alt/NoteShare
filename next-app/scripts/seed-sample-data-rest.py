import json
import urllib.request
import urllib.parse

API_KEY = 'AIzaSyDZyyejjFwpWn6uZdlQpW8xO_o6vtCL4rE'
PROJECT_DB = 'https://noteshare-3-default-rtdb.firebaseio.com'
import time

PASSWORD = 'Seed1234!'
EMAIL = f'seed.user.{int(time.time())}@example.com'

sign_up_url = f'https://identitytoolkit.googleapis.com/v1/accounts:signUp?key={API_KEY}'

payload = json.dumps({'email': EMAIL, 'password': PASSWORD, 'returnSecureToken': True}).encode('utf-8')
req = urllib.request.Request(sign_up_url, data=payload, headers={'Content-Type': 'application/json'})
try:
    with urllib.request.urlopen(req) as response:
        result = json.loads(response.read().decode('utf-8'))
except urllib.error.HTTPError as err:
    body = err.read().decode('utf-8')
    print('Sign-up error body:', body)
    raise

id_token = result['idToken']
print('Signed in as', EMAIL)

users = {
    'user_sample_seller_1': {
        'email': 'seller1@vitstudent.ac.in',
        'name': 'Seller One',
        'coins': 50,
        'createdAt': '2026-05-17T18:00:00.000Z',
        'status': 'active',
        'isAdmin': False,
    },
    'user_sample_seller_2': {
        'email': 'seller2@vitstudent.ac.in',
        'name': 'Seller Two',
        'coins': 30,
        'createdAt': '2026-05-17T18:05:00.000Z',
        'status': 'active',
        'isAdmin': False,
    },
}
notes = {
    'sample_note_1': {
        'subject_name': 'Data Structures',
        'course_code': 'CSE201',
        'slot': 'A1',
        'faculty_name': 'Dr. Sharma',
        'price': 25,
        'seller_name': 'Seller One',
        'seller_id': 'user_sample_seller_1',
        'status': 'available',
    },
    'sample_note_2': {
        'subject_name': 'Database Systems',
        'course_code': 'CSE202',
        'slot': 'B2',
        'faculty_name': 'Prof. Kumar',
        'price': 30,
        'seller_name': 'Seller Two',
        'seller_id': 'user_sample_seller_2',
        'status': 'available',
    },
    'sample_note_3': {
        'subject_name': 'Computer Networks',
        'course_code': 'CSE203',
        'slot': 'C1',
        'faculty_name': 'Dr. Nair',
        'price': 20,
        'seller_name': 'Seller One',
        'seller_id': 'user_sample_seller_1',
        'status': 'available',
    },
}

for path, payload_data in [('/users.json', users), ('/notes.json', notes)]:
    url = f'{PROJECT_DB}{path}?auth={urllib.parse.quote(id_token)}'
    req = urllib.request.Request(url, data=json.dumps(payload_data).encode('utf-8'), headers={'Content-Type': 'application/json'}, method='PATCH')
    try:
        with urllib.request.urlopen(req) as response:
            print('Seeded', path, response.status)
    except urllib.error.HTTPError as err:
        print('Seed error for', path, err.code)
        print(err.read().decode('utf-8'))
        raise
