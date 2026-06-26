#!/usr/bin/env node

/**
 * Email Configuration Test for Vercel Deployment
 * 
 * This script verifies that your Gmail SMTP configuration works correctly.
 * Run this BEFORE deploying to Vercel to catch configuration issues early.
 * 
 * Usage:
 *   npm run test:email-config
 *   OR
 *   node scripts/test-email-config.js
 */

require('dotenv').config({ path: '.env.local' });
const nodemailer = require('nodemailer');
const chalk = require('chalk') || { green: (s) => `✓ ${s}`, red: (s) => `✗ ${s}`, yellow: (s) => `⚠ ${s}`, blue: (s) => s };

const tests = {
  passed: 0,
  failed: 0,
  warnings: 0,
};

function log(type, message) {
  const icons = {
    success: '✅',
    error: '❌',
    warning: '⚠️',
    info: 'ℹ️',
    test: '🧪',
  };
  console.log(`${icons[type] || ''} ${message}`);
}

function section(title) {
  console.log(`\n${'='.repeat(60)}`);
  console.log(`  ${title}`);
  console.log(`${'='.repeat(60)}\n`);
}

async function runTests() {
  section('Email Configuration Test Suite');

  // Test 1: Environment variables
  log('test', 'Test 1: Checking environment variables...');
  const user = process.env.GMAIL_USERNAME;
  const pass = process.env.GMAIL_APP_PASSWORD;
  const host = process.env.SMTP_HOST || 'smtp.gmail.com';
  const port = parseInt(process.env.SMTP_PORT || '587');
  const fromEmail = process.env.FROM_EMAIL || user;

  if (!user) {
    log('error', 'GMAIL_USERNAME not set');
    tests.failed++;
  } else {
    log('success', `GMAIL_USERNAME: ${user}`);
    tests.passed++;
  }

  if (!pass) {
    log('error', 'GMAIL_APP_PASSWORD not set');
    tests.failed++;
  } else if (pass.length < 15) {
    log('warning', 'GMAIL_APP_PASSWORD appears too short (should be 16 chars). Gmail app passwords are typically 16 characters.');
    tests.warnings++;
  } else {
    log('success', `GMAIL_APP_PASSWORD: ${pass.substring(0, 4)}${'*'.repeat(pass.length - 4)}`);
    tests.passed++;
  }

  log('success', `SMTP_HOST: ${host}`);
  log('success', `SMTP_PORT: ${port}`);
  log('success', `FROM_EMAIL: ${fromEmail}`);

  // Test 2: Port validation
  log('test', '\nTest 2: Validating port configuration...');
  if (port === 25) {
    log('error', 'Port 25 is BLOCKED by Vercel. Use Port 587 (TLS) or 465 (SSL)');
    tests.failed++;
  } else if (port === 587 || port === 465) {
    log('success', `Port ${port} is supported by Vercel`);
    if (port === 587) {
      log('info', 'Using Port 587 (TLS) - Recommended ✓');
    } else {
      log('info', 'Using Port 465 (SSL) - Also supported');
    }
    tests.passed++;
  } else {
    log('warning', `Port ${port} is unusual. Most use 25, 465, or 587`);
    tests.warnings++;
  }

  // Test 3: Email format validation
  log('test', '\nTest 3: Validating email formats...');
  const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  if (emailRegex.test(user)) {
    log('success', `${user} is valid email format`);
    tests.passed++;
  } else {
    log('error', `${user} is not valid email format`);
    tests.failed++;
  }

  if (emailRegex.test(fromEmail)) {
    log('success', `${fromEmail} is valid email format`);
    tests.passed++;
  } else {
    log('error', `${fromEmail} is not valid email format`);
    tests.failed++;
  }

  // Test 4: SMTP Connection
  section('Attempting SMTP Connection');
  
  if (tests.failed > 0) {
    log('error', 'Skipping connection test due to configuration errors above');
    log('info', 'Fix the issues above and try again');
  } else {
    log('test', `Connecting to ${host}:${port}...`);
    
    try {
      const transporter = nodemailer.createTransport({
        host: host,
        port: port,
        secure: port === 465, // true for 465 (SSL), false for 587 (TLS)
        requireTLS: port === 587,
        auth: {
          user: user,
          pass: pass.replace(/\s+/g, ''),
        },
        tls: {
          rejectUnauthorized: true,
        },
      });

      await transporter.verify();
      log('success', 'SMTP Connection successful! ✓');
      tests.passed++;

      // Test 5: Send test email
      log('test', '\nTest 5: Sending test email...');
      
      const result = await transporter.sendMail({
        from: `NoteShare <${fromEmail}>`,
        to: user,
        subject: 'NoteShare Email Configuration Test',
        html: `
          <div style="font-family: Arial, sans-serif; max-width: 500px;">
            <h2 style="color: #28a745;">✅ Email Configuration Test</h2>
            <p>Your Vercel email setup is working correctly!</p>
            <div style="background: #f0f0f0; padding: 10px; border-radius: 5px;">
              <p><strong>Test Details:</strong></p>
              <ul>
                <li>From: ${fromEmail}</li>
                <li>To: ${user}</li>
                <li>Port: ${port}</li>
                <li>Time: ${new Date().toLocaleString()}</li>
              </ul>
            </div>
            <p style="margin-top: 20px; color: #666;">
              If you received this email, your OTP system is ready for Vercel deployment!
            </p>
          </div>
        `,
      });

      log('success', 'Test email sent successfully! ✓');
      log('info', `Message ID: ${result.messageId}`);
      tests.passed++;
      
    } catch (error) {
      log('error', `Connection failed: ${error.message}`);
      tests.failed++;
      
      log('info', '\nCommon causes:');
      if (error.message.includes('ECONNREFUSED')) {
        log('info', '  → Port ${port} connection refused. Vercel blocks Port 25. Use 587 or 465.');
      }
      if (error.message.includes('Invalid login')) {
        log('info', '  → Gmail authentication failed. Check GMAIL_APP_PASSWORD is correct.');
        log('info', '  → Must use app-specific password, not regular Gmail password.');
      }
      if (error.message.includes('EHOSTUNREACH')) {
        log('info', '  → Cannot reach SMTP host. Check internet connection.');
        log('info', '  → On Vercel, this might be a regional issue.');
      }
    }
  }

  // Summary
  section('Test Summary');
  
  const total = tests.passed + tests.failed + tests.warnings;
  const percentage = Math.round((tests.passed / total) * 100);
  
  console.log(`Tests Passed:  ${tests.passed}/${total}`);
  console.log(`Tests Failed:  ${tests.failed}/${total}`);
  console.log(`Warnings:      ${tests.warnings}/${total}`);
  console.log(`Success Rate:  ${percentage}%\n`);

  if (tests.failed === 0 && tests.warnings === 0) {
    log('success', 'All tests passed! ✓ Ready for Vercel deployment.');
    return 0;
  } else if (tests.failed === 0) {
    log('warning', 'Tests passed with warnings. Review above and proceed carefully.');
    return 0;
  } else {
    log('error', 'Some tests failed. Fix issues above before deploying.');
    return 1;
  }
}

// Run tests
runTests().then(code => {
  process.exit(code);
}).catch(error => {
  log('error', `Unexpected error: ${error.message}`);
  process.exit(1);
});
