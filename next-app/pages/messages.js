import { useEffect, useState } from 'react';
import { useRouter } from 'next/router';
import Header from '../components/Header';
import { auth } from '../lib/firebase';
import { onAuthStateChanged } from 'firebase/auth';
import { getUserProfile } from '../lib/auth';
import { getUserChats, getChatMessages, sendMessage, searchUsersByName, createChat } from '../lib/db';

export default function MessagesPage() {
  const router = useRouter();
  const [currentUser, setCurrentUser] = useState(null);
  const [chats, setChats] = useState([]);
  const [selectedChat, setSelectedChat] = useState(null);
  const [messages, setMessages] = useState([]);
  const [newMessage, setNewMessage] = useState('');
  const [searchTerm, setSearchTerm] = useState('');
  const [searchResults, setSearchResults] = useState([]);
  const [loading, setLoading] = useState(true);
  const [chatLoading, setChatLoading] = useState(false);

  useEffect(() => {
    const unsubscribe = onAuthStateChanged(auth, async (user) => {
      if (!user) {
        router.push('/login');
        return;
      }
      const profile = await getUserProfile(user);
      setCurrentUser({ uid: profile.uid, name: profile.name, email: profile.email });
      await refreshChats(user.uid);
      setLoading(false);
    });
    return unsubscribe;
  }, [router]);

  async function refreshChats(uid) {
    const activeChats = await getUserChats(uid);
    setChats(activeChats);
    if (activeChats.length > 0 && !selectedChat) {
      setSelectedChat(activeChats[0]);
      loadChat(activeChats[0]);
    }
  }

  async function loadChat(chat) {
    setSelectedChat(chat);
    setChatLoading(true);
    const messages = await getChatMessages(chat.id);
    setMessages(messages);
    setChatLoading(false);
  }

  async function handleSend() {
    if (!selectedChat || !newMessage.trim() || !currentUser) return;
    const messagePayload = {
      sender_id: currentUser.uid,
      sender_name: currentUser.name,
      text: newMessage.trim(),
      timestamp: Date.now()
    };
    await sendMessage(selectedChat.id, messagePayload);
    setNewMessage('');
    await loadChat(selectedChat);
  }

  async function handleSearchUsers(event) {
    event.preventDefault();
    if (!searchTerm.trim()) return;
    const results = await searchUsersByName(searchTerm.trim());
    const filtered = results.filter((user) => user.id !== currentUser.uid);
    setSearchResults(filtered);
  }

  async function startChatWith(user) {
    const existingChat = chats.find((chat) => chat.participants.includes(user.id));
    if (existingChat) {
      loadChat(existingChat);
      return;
    }
    const chatId = await createChat({
      participants: [currentUser.uid, user.id],
      participant_names: [currentUser.name, user.name],
      created_at: Date.now()
    });
    const updatedChats = await getUserChats(currentUser.uid);
    setChats(updatedChats);
    const nextChat = updatedChats.find((chat) => chat.id === chatId);
    if (nextChat) {
      loadChat(nextChat);
    }
  }

  return (
    <div>
      <Header currentUser={currentUser} />
      <main className="container-fluid mt-4">
        <div className="row">
          <div className="col-lg-4 mb-4">
            <div className="card h-100">
              <div className="card-body">
                <h5>Conversations</h5>
                {loading ? (
                  <div className="text-center py-4"><div className="spinner-border text-primary"></div></div>
                ) : chats.length === 0 ? (
                  <div className="text-muted">No conversations yet.</div>
                ) : (
                  <div className="list-group">
                    {chats.map((chat) => {
                      const otherName = chat.participant_names?.find((name) => name !== currentUser.name) || 'Chat';
                      return (
                        <button key={chat.id} className={`list-group-item list-group-item-action ${selectedChat?.id === chat.id ? 'active' : ''}`} onClick={() => loadChat(chat)}>
                          {otherName}
                        </button>
                      );
                    })}
                  </div>
                )}
              </div>
            </div>
            <div className="card mt-3">
              <div className="card-body">
                <h5>Start New Chat</h5>
                <form onSubmit={handleSearchUsers}>
                  <div className="input-group mb-3">
                    <input value={searchTerm} onChange={(e) => setSearchTerm(e.target.value)} className="form-control" placeholder="Search by name" />
                    <button className="btn btn-primary" type="submit">Search</button>
                  </div>
                </form>
                {searchResults.map((user) => (
                  <button key={user.id} className="btn btn-outline-secondary btn-sm w-100 mb-2" onClick={() => startChatWith(user)}>{user.name}</button>
                ))}
              </div>
            </div>
          </div>
          <div className="col-lg-8">
            <div className="card h-100">
              <div className="card-body d-flex flex-column">
                <h5>Chat</h5>
                {!selectedChat ? (
                  <div className="text-muted mt-4">Select a conversation to open it.</div>
                ) : (
                  <>
                    <div className="flex-grow-1 mb-3" style={{ overflowY: 'auto', maxHeight: '60vh' }}>
                      {chatLoading ? (
                        <div className="text-center py-4"><div className="spinner-border text-primary"></div></div>
                      ) : messages.length === 0 ? (
                        <div className="text-muted">No messages yet. Send the first message.</div>
                      ) : messages.map((message) => (
                        <div key={message.id} className={`mb-3 p-3 rounded ${message.sender_id === currentUser.uid ? 'bg-primary text-white ms-auto' : 'bg-light text-dark'}`} style={{ maxWidth: '80%' }}>
                          <div>{message.text}</div>
                          <div className="small text-muted mt-2 text-end">{new Date(message.timestamp).toLocaleTimeString()}</div>
                        </div>
                      ))}
                    </div>
                    <div className="input-group">
                      <input value={newMessage} onChange={(e) => setNewMessage(e.target.value)} className="form-control" placeholder="Type a message..." />
                      <button className="btn btn-primary" type="button" onClick={handleSend}>Send</button>
                    </div>
                  </>
                )}
              </div>
            </div>
          </div>
        </div>
      </main>
    </div>
  );
}
