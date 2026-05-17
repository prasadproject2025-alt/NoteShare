import { useState, useEffect } from 'react';
import { useRouter } from 'next/router';
import Header from '../components/Header';

export default function VerifyOtpPage() {
  const router = useRouter();
  const [otp, setOtp] = useState('');
  const [email, setEmail] = useState('');
  const [error, setError] = useState('');
  const [success, setSuccess] = useState('');
  const [loading, setLoading] = useState(false);
  const [timeLeft, setTimeLeft] = useState(600); // 10 minutes

  useEffect(() => {
    // Get email from URL query or localStorage
    const emailParam = router.query.email || localStorage.getItem('pendingVerificationEmail');
    if (emailParam) {
      setEmail(emailParam);
    } else {
      router.push('/create-account');
    }
  }, [router.query.email]);

  // Countdown timer
  useEffect(() => {
    if (timeLeft <= 0) return;
    const timer = setTimeout(() => setTimeLeft(timeLeft - 1), 1000);
    return () => clearTimeout(timer);
  }, [timeLeft]);

  const formatTime = (seconds) => {
    const mins = Math.floor(seconds / 60);
    const secs = seconds % 60;
    return `${mins}:${secs.toString().padStart(2, '0')}`;
  };

  async function handleVerify(event) {
    event.preventDefault();
    setError('');
    setSuccess('');

    if (otp.length !== 6) {
      setError('OTP must be 6 digits');
      return;
    }

    setLoading(true);
    try {
      // Get stored credentials from localStorage
      const password = localStorage.getItem('pendingVerificationPassword');
      const name = localStorage.getItem('pendingVerificationName');

      const response = await fetch('/api/verify-otp', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ email, otp, password, name }),
      });

      const data = await response.json();

      if (!response.ok) {
        setError(data.error || 'Verification failed');
        return;
      }

      setSuccess('Email verified successfully! Account created.');
      localStorage.removeItem('pendingVerificationEmail');
      localStorage.removeItem('pendingVerificationName');
      localStorage.removeItem('pendingVerificationPassword');
      setTimeout(() => {
        router.push('/login');
      }, 1500);
    } catch (err) {
      setError(err.message || 'Verification failed');
    } finally {
      setLoading(false);
    }
  }

  async function handleResendOtp() {
    setLoading(true);
    try {
      const response = await fetch('/api/send-otp', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ email, name: email.split('@')[0] }),
      });

      const data = await response.json();

      if (!response.ok) {
        setError(data.error || 'Failed to resend OTP');
        return;
      }

      setSuccess('OTP resent successfully!');
      setTimeLeft(600); // Reset timer
      setOtp('');
    } catch (err) {
      setError(err.message || 'Failed to resend OTP');
    } finally {
      setLoading(false);
    }
  }

  return (
    <div>
      <Header currentUser={null} />
      <main className="container mt-5">
        <div className="row justify-content-center">
          <div className="col-md-6">
            <div className="card shadow-sm">
              <div className="card-header bg-info text-white">
                <h3 className="mb-0">Verify Your Email</h3>
              </div>
              <div className="card-body">
                <p className="text-muted">
                  We've sent a verification code to <strong>{email}</strong>
                </p>

                {error && <div className="alert alert-danger">{error}</div>}
                {success && <div className="alert alert-success">{success}</div>}

                <form onSubmit={handleVerify}>
                  <div className="mb-3">
                    <label htmlFor="otp" className="form-label">
                      Enter OTP (6 digits)
                    </label>
                    <input
                      id="otp"
                      type="text"
                      className="form-control form-control-lg text-center"
                      value={otp}
                      onChange={(e) => setOtp(e.target.value.replace(/\D/g, '').slice(0, 6))}
                      placeholder="000000"
                      maxLength="6"
                      required
                      style={{ fontSize: '24px', letterSpacing: '10px' }}
                    />
                  </div>

                  <div className="mb-3 text-center">
                    <small className={timeLeft < 60 ? 'text-danger' : 'text-muted'}>
                      OTP expires in: <strong>{formatTime(timeLeft)}</strong>
                    </small>
                  </div>

                  <button
                    type="submit"
                    className="btn btn-info w-100"
                    disabled={loading || otp.length !== 6}
                  >
                    {loading ? 'Verifying...' : 'Verify Email'}
                  </button>
                </form>

                <hr />

                <div className="text-center">
                  <small className="text-muted">Didn't receive the OTP?</small>
                  <br />
                  <button
                    onClick={handleResendOtp}
                    className="btn btn-link p-0"
                    disabled={loading || timeLeft > 540} // Can resend after 1 minute
                  >
                    Resend OTP
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </main>
    </div>
  );
}
