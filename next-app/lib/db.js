import { ref, get, push, update, query, orderByChild, equalTo } from 'firebase/database';
import { database } from './firebase';

export async function getUserData(uid) {
  try {
    const snap = await get(ref(database, `users/${uid}`));
    return snap.exists() ? snap.val() : null;
  } catch (err) {
    console.error('getUserData read error', err?.message || err);
    return null;
  }
}

export async function searchNotes({ courseCode, subjectName, slot }) {
  try {
    const snap = await get(ref(database, 'notes'));
    if (!snap.exists()) return [];

    const notes = Object.entries(snap.val()).map(([id, note]) => ({ id, ...note }));
  return notes.filter((note) => {
    if (note.status === 'sold') return false;
    if (courseCode && !note.course_code.toLowerCase().includes(courseCode.toLowerCase())) return false;
    if (subjectName && !note.subject_name.toLowerCase().includes(subjectName.toLowerCase())) return false;
    if (slot && note.slot !== slot) return false;
    return true;
  });
  } catch (err) {
    console.error('searchNotes read error', err?.message || err);
    return [];
  }
}

export async function createNote(note) {
  try {
    const noteRef = await push(ref(database, 'notes'), note);
    return noteRef.key;
  } catch (err) {
    console.error('createNote failed', err?.message || err);
    throw new Error('Database write failed');
  }
}

export async function buyNote(noteId, buyerId, buyerName, buyerEmail, price) {
  try {
    const noteRef = ref(database, `notes/${noteId}`);
    const noteSnap = await get(noteRef);
    if (!noteSnap.exists()) throw new Error('Note not found');

    const note = noteSnap.val();
    if (note.status === 'sold') throw new Error('Note already sold');
    if (note.seller_id === buyerId) throw new Error('Cannot buy your own note');

    // Deduct buyer coins and credit seller coins
    const buyerRef = ref(database, `users/${buyerId}`);
    const sellerRef = ref(database, `users/${note.seller_id}`);

    const buyerSnap = await get(buyerRef);
    const sellerSnap = await get(sellerRef);
    const buyer = buyerSnap.val() || {};
    const seller = sellerSnap.val() || {};

    if ((buyer.coins || 0) < price) {
      throw new Error('Insufficient coins');
    }

    await update(buyerRef, { coins: (buyer.coins || 0) - price });
    await update(sellerRef, { coins: (seller.coins || 0) + price });
    await update(noteRef, {
      status: 'sold',
      buyer_id: buyerId,
      buyer_name: buyerName,
      buyer_email: buyerEmail,
      sold_at: Date.now(),
    });

    await push(ref(database, 'coin_transactions'), {
      user_id: buyerId,
      type: 'spend',
      coins: price,
      description: `Purchased note ${note.subject_name}`,
      timestamp: Date.now(),
    });

    await push(ref(database, 'coin_transactions'), {
      user_id: note.seller_id,
      type: 'earn',
      coins: price,
      description: `Sold note ${note.subject_name}`,
      timestamp: Date.now(),
    });

    return note;
  } catch (err) {
    console.error('buyNote failed', err?.message || err);
    throw err;
  }
}

export async function loadSharedNotes({ slot, courseCode, subjectName }) {
  try {
    const snap = await get(ref(database, 'shared_notes'));
    if (!snap.exists()) return [];

    const notes = Object.entries(snap.val()).map(([id, note]) => ({ id, ...note }));
  return notes.filter((note) => {
    if (slot && note.batch !== slot) return false;
    if (courseCode && !note.course_code.toLowerCase().includes(courseCode.toLowerCase())) return false;
    if (subjectName && !note.subject_name.toLowerCase().includes(subjectName.toLowerCase())) return false;
    return true;
  });
  } catch (err) {
    console.error('loadSharedNotes read error', err?.message || err);
    return [];
  }
}

export async function createSharedNote(note) {
  try {
    const refSnap = await push(ref(database, 'shared_notes'), note);
    return refSnap.key;
  } catch (err) {
    console.error('createSharedNote failed', err?.message || err);
    throw new Error('Database write failed');
  }
}

export async function loadRentalNotes({ courseCode, slot }) {
  try {
    const snap = await get(ref(database, 'rental_notes'));
    if (!snap.exists()) return [];

    const notes = Object.entries(snap.val()).map(([id, note]) => ({ id, ...note }));
  return notes.filter((note) => {
    if (courseCode && !note.course_code.toLowerCase().includes(courseCode.toLowerCase())) return false;
    if (slot && note.slot !== slot) return false;
    return note.available !== false;
  });
  } catch (err) {
    console.error('loadRentalNotes read error', err?.message || err);
    return [];
  }
}

export async function createRentalNote(note) {
  try {
    const refSnap = await push(ref(database, 'rental_notes'), note);
    return refSnap.key;
  } catch (err) {
    console.error('createRentalNote failed', err?.message || err);
    throw new Error('Database write failed');
  }
}

export async function getCoinTransactions(userId) {
  try {
    const snap = await get(ref(database, 'coin_transactions'));
    if (!snap.exists()) return [];
    const transactions = Object.entries(snap.val()).map(([id, tx]) => ({ id, ...tx }));
    return transactions
      .filter((tx) => tx.user_id === userId)
      .sort((a, b) => b.timestamp - a.timestamp);
  } catch (err) {
    console.error('getCoinTransactions read error', err?.message || err);
    return [];
  }
}

export async function addCoins(userId, coins, price) {
  try {
    const userRef = ref(database, `users/${userId}`);
    const userSnap = await get(userRef);
    if (!userSnap.exists()) {
      throw new Error('User does not exist');
    }
    const user = userSnap.val();
    const newBalance = (user.coins || 0) + coins;
    await update(userRef, { coins: newBalance });
    await push(ref(database, 'coin_transactions'), {
      user_id: userId,
      type: 'purchase',
      coins,
      price,
      description: `Purchased ${coins} coins for ₹${price}`,
      timestamp: Date.now()
    });
    return newBalance;
  } catch (err) {
    console.error('addCoins failed', err?.message || err);
    throw err;
  }
}

export async function getUserChats(userId) {
  try {
    const snap = await get(ref(database, 'chats'));
    if (!snap.exists()) return [];
    const chats = Object.entries(snap.val()).map(([id, chat]) => ({ id, ...chat }));
    return chats.filter((chat) => Array.isArray(chat.participants) && chat.participants.includes(userId));
  } catch (err) {
    console.error('getUserChats read error', err?.message || err);
    return [];
  }
}

export async function getChatMessages(chatId) {
  try {
    const snap = await get(ref(database, `chats/${chatId}/messages`));
    if (!snap.exists()) return [];
    return Object.entries(snap.val()).map(([id, message]) => ({ id, ...message })).sort((a, b) => a.timestamp - b.timestamp);
  } catch (err) {
    console.error('getChatMessages read error', err?.message || err);
    return [];
  }
}

export async function createChat(chat) {
  try {
    const refSnap = await push(ref(database, 'chats'), chat);
    return refSnap.key;
  } catch (err) {
    console.error('createChat failed', err?.message || err);
    throw new Error('Database write failed');
  }
}

export async function sendMessage(chatId, message) {
  try {
    const refSnap = await push(ref(database, `chats/${chatId}/messages`), message);
    return refSnap.key;
  } catch (err) {
    console.error('sendMessage failed', err?.message || err);
    throw new Error('Database write failed');
  }
}

export async function getAllUsers() {
  try {
    const snap = await get(ref(database, 'users'));
    if (!snap.exists()) return [];
    return Object.entries(snap.val()).map(([id, user]) => ({ id, ...user }));
  } catch (err) {
    console.error('getAllUsers read error', err?.message || err);
    return [];
  }
}

export async function searchUsersByName(term) {
  const users = await getAllUsers();
  return users.filter((user) => user.name?.toLowerCase().includes(term.toLowerCase()));
}
