const { initializeApp } = require('firebase/app');
const { getDatabase, ref, update } = require('firebase/database');

const firebaseConfig = {
  apiKey: process.env.NEXT_PUBLIC_FIREBASE_API_KEY || 'AIzaSyDZyyejjFwpWn6uZdlQpW8xO_o6vtCL4rE',
  authDomain: process.env.NEXT_PUBLIC_FIREBASE_AUTH_DOMAIN || 'noteshare-3.firebaseapp.com',
  databaseURL: process.env.NEXT_PUBLIC_FIREBASE_DATABASE_URL || 'https://noteshare-3-default-rtdb.firebaseio.com',
  projectId: process.env.NEXT_PUBLIC_FIREBASE_PROJECT_ID || 'noteshare-3',
  storageBucket: process.env.NEXT_PUBLIC_FIREBASE_STORAGE_BUCKET || 'noteshare-3.firebasestorage.app',
  messagingSenderId: process.env.NEXT_PUBLIC_FIREBASE_MESSAGING_SENDER_ID || '147584632956',
  appId: process.env.NEXT_PUBLIC_FIREBASE_APP_ID || '1:147584632956:web:54f02157d6e298082f61f9',
  measurementId: process.env.NEXT_PUBLIC_FIREBASE_MEASUREMENT_ID || 'G-YJQFNEVPTZ',
};

const app = initializeApp(firebaseConfig);
const db = getDatabase(app);

const data = {
  users: {
    user_sample_seller_1: {
      email: 'seller1@vitstudent.ac.in',
      name: 'Seller One',
      coins: 50,
      createdAt: '2026-05-17T18:00:00.000Z',
      status: 'active',
      isAdmin: false,
    },
    user_sample_seller_2: {
      email: 'seller2@vitstudent.ac.in',
      name: 'Seller Two',
      coins: 30,
      createdAt: '2026-05-17T18:05:00.000Z',
      status: 'active',
      isAdmin: false,
    },
  },
  notes: {
    sample_note_1: {
      subject_name: 'Data Structures',
      course_code: 'CSE201',
      slot: 'A1',
      faculty_name: 'Dr. Sharma',
      price: 25,
      seller_name: 'Seller One',
      seller_id: 'user_sample_seller_1',
      status: 'available',
    },
    sample_note_2: {
      subject_name: 'Database Systems',
      course_code: 'CSE202',
      slot: 'B2',
      faculty_name: 'Prof. Kumar',
      price: 30,
      seller_name: 'Seller Two',
      seller_id: 'user_sample_seller_2',
      status: 'available',
    },
    sample_note_3: {
      subject_name: 'Computer Networks',
      course_code: 'CSE203',
      slot: 'C1',
      faculty_name: 'Dr. Nair',
      price: 20,
      seller_name: 'Seller One',
      seller_id: 'user_sample_seller_1',
      status: 'available',
    },
  },
};

async function seed() {
  const updates = {};

  updates['/users'] = data.users;
  updates['/notes'] = data.notes;

  const { update: updateDB } = require('firebase/database');
  await updateDB(ref(db), updates);
  console.log('Seeded sample users and notes to Firebase.');
}

seed().catch((err) => {
  console.error('Failed to seed sample data:', err);
  process.exit(1);
});
