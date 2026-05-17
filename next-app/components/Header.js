import Link from 'next/link';
import { useRouter } from 'next/router';
import { logoutUser } from '../lib/auth';

export default function Header({ currentUser }) {
  const router = useRouter();

  async function handleLogout() {
    await logoutUser();
    router.push('/login');
  }

  return (
    <nav className="navbar navbar-expand-lg navbar-dark bg-dark">
      <div className="container">
        <Link href="/" className="navbar-brand">
          NoteShare
        </Link>

        <button className="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
          <span className="navbar-toggler-icon"></span>
        </button>

        <div className="collapse navbar-collapse" id="navbarNav">
          <ul className="navbar-nav ms-auto">
            {currentUser ? (
              <>
                <li className="nav-item">
                  <Link href="/" className="nav-link">
                    Home
                  </Link>
                </li>
                <li className="nav-item">
                  <Link href="/coins" className="nav-link">
                    Coins
                  </Link>
                </li>
                <li className="nav-item">
                  <Link href="/messages" className="nav-link">
                    Messages
                  </Link>
                </li>
                {currentUser?.isAdmin && (
                  <li className="nav-item">
                    <Link href="/admin/dashboard" className="nav-link">
                      Admin
                    </Link>
                  </li>
                )}
                <li className="nav-item dropdown">
                  <a className="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                    {currentUser.name || 'Profile'}
                  </a>
                  <ul className="dropdown-menu dropdown-menu-end">
                    <li>
                      <Link href="/profile" className="dropdown-item">
                        My Profile
                      </Link>
                    </li>
                    <li>
                      <Link href="/edit-profile" className="dropdown-item">
                        Edit Profile
                      </Link>
                    </li>
                    <li><hr className="dropdown-divider" /></li>
                    <li><button className="dropdown-item" onClick={handleLogout}>Logout</button></li>
                  </ul>
                </li>
              </>
            ) : (
              <>
                <li className="nav-item">
                  <Link href="/login" className="nav-link">
                    Login
                  </Link>
                </li>
                <li className="nav-item">
                  <Link href="/create-account" className="nav-link text-warning">
                    Create Account
                  </Link>
                </li>
              </>
            )}
          </ul>
        </div>
      </div>
    </nav>
  );
}
