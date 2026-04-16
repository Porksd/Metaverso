import { Modal } from '@mantine/core';
import { __ } from '@wordpress/i18n';
import React, { useRef, useState } from 'react';
import { Icons } from '../icons';

const parseCSV = (file) => {
  return new Promise((resolve, reject) => {
    const reader = new FileReader();

    reader.onload = (e) => {
      const text = e.target.result;
      const lines = text.trim().split(/\r?\n/);
      const headers = lines[0]
        .split(',')
        .map((header) => header.trim().toLowerCase());

      const requiredHeaders = ['first name', 'last name', 'email'];

      const hasAllHeaders = requiredHeaders.every((h) => headers.includes(h));

      if (!hasAllHeaders) {
        reject(
          new Error(
            __(
              'Invalid CSV headers. Required headers are "First Name", "Last Name", and "Email".',
              'edwiser-bridge-pro'
            )
          )
        );
        return;
      }
      const firstNameIndex = headers.indexOf('first name');
      const lastNameIndex = headers.indexOf('last name');
      const emailIndex = headers.indexOf('email');

      const users = lines.slice(1).map((line) => {
        const values = line.split(',');
        return {
          first_name: values[firstNameIndex]?.trim() || '',
          last_name: values[lastNameIndex]?.trim() || '',
          email: values[emailIndex]?.trim() || '',
        };
      });

      resolve(users);
    };

    reader.onerror = () => {
      reject(new Error(__('Failed to read the file.', 'edwiser-bridge-pro')));
    };

    reader.readAsText(file);
  });
};

function EnrollMultipleUsers({
  enrollMultipleOpened,
  closeEnrollMultiple,
  onUploadSuccess,
  availableSeats,
}) {
  const [file, setFile] = useState(null);
  const [dragActive, setDragActive] = useState(false);
  const [error, setError] = useState(null);
  const inputRef = useRef();
  const [isUploading, setIsUploading] = useState(false);

  // Handle file selection
  const handleFileChange = (e) => {
    const selected = e.target.files[0];
    if (selected) {
      if (
        !selected.name.toLowerCase().endsWith('.csv') &&
        selected.type !== 'text/csv'
      ) {
        setError(
          __(
            'Invalid file format. Please upload a CSV file.',
            'edwiser-bridge-pro'
          )
        );
        setFile(null);
        return;
      }
      setFile(selected);
      setError(null);
    }
  };

  // Handle drag events
  const handleDragOver = (e) => {
    e.preventDefault();
    e.stopPropagation();
    setDragActive(true);
  };

  const handleDragLeave = (e) => {
    e.preventDefault();
    e.stopPropagation();
    setDragActive(false);
  };

  const handleDrop = (e) => {
    e.preventDefault();
    e.stopPropagation();
    setDragActive(false);
    if (e.dataTransfer.files && e.dataTransfer.files[0]) {
      const droppedFile = e.dataTransfer.files[0];
      if (
        !droppedFile.name.toLowerCase().endsWith('.csv') &&
        droppedFile.type !== 'text/csv'
      ) {
        setError(
          __(
            'Invalid file format. Please upload a CSV file.',
            'edwiser-bridge-pro'
          )
        );
        setFile(null);
        return;
      }
      setFile(droppedFile);
      setError(null);
    }
  };

  // Remove selected file
  const handleRemoveFile = () => {
    setFile(null);
    setError(null);
  };

  // Upload action
  const handleUpload = async () => {
    if (!file) return;

    setIsUploading(true);
    setError(null);

    try {
      const users = await parseCSV(file);

      // Check if there are enough available seats
      if (users.length > availableSeats) {
        setError(
          __(
            'Not enough seats available to enroll all users. Please add more seats or reduce CSV records.',
            'edwiser-bridge-pro'
          )
        );
        return;
      }

      onUploadSuccess(users);
      setFile(null);
    } catch (err) {
      setError(err.message);
    } finally {
      setIsUploading(false);
    }
  };

  const handleModalClose = () => {
    setFile(null);
    setError(null);
    closeEnrollMultiple();
  };

  return (
    <Modal
      opened={enrollMultipleOpened}
      onClose={handleModalClose}
      title={__('Upload users list', 'edwiser-bridge-pro')}
      withinPortal={false}
      closeOnClickOutside={false}
      size="lg"
    >
      <div className="enrollment-actions__enroll-multiple-modal">
        {!file ? (
          <div
            className={`enroll-multiple-modal__dropzone${
              dragActive ? ' drag-active' : ''
            }`}
            onDragOver={handleDragOver}
            onDragLeave={handleDragLeave}
            onDrop={handleDrop}
            onClick={() => inputRef.current.click()}
            tabIndex={0}
            role="button"
            aria-label={__(
              'Drop or browse your file here',
              'edwiser-bridge-pro'
            )}
          >
            <input
              type="file"
              accept=".csv"
              ref={inputRef}
              style={{ display: 'none' }}
              onChange={handleFileChange}
              tabIndex={-1}
            />
            <div className="dropzone__icon">
              <Icons.upload />
            </div>
            <div className="dropzone__prompt">
              {__('Drop or browse your file here', 'edwiser-bridge-pro')}
            </div>
            <div className="dropzone__format">
              {__('File format', 'edwiser-bridge-pro')}:{' '}
              <b>{__('First name, last name & email', 'edwiser-bridge-pro')}</b>
            </div>
          </div>
        ) : (
          <div className="enroll-multiple-modal__file-preview">
            <span className="file-preview__name">{file.name}</span>
            <button
              className="file-preview__replace"
              onClick={handleRemoveFile}
            >
              {__('Upload another file', 'edwiser-bridge-pro')}
            </button>
          </div>
        )}
        {error && <div className="enroll-multiple-modal__error">{error}</div>}
        <div className="enroll-multiple-modal__actions">
          <a
            href="/wp-content/plugins/edwiser-bridge-pro/public/upload_users_sample.csv"
            download
            className="actions__sample-file"
          >
            {__('Download sample CSV', 'edwiser-bridge-pro')}
          </a>
          <div className="actions__buttons">
            <button
              onClick={handleUpload}
              disabled={!file || isUploading}
              className="btn__action-confirm"
            >
              {isUploading && <Icons.loader />}
              {__('Upload list', 'edwiser-bridge-pro')}
            </button>
            <button
              onClick={handleModalClose}
              className="btn__action-cancel"
              disabled={isUploading}
            >
              {__('Cancel', 'edwiser-bridge-pro')}
            </button>
          </div>
        </div>
      </div>
    </Modal>
  );
}

export default EnrollMultipleUsers;
