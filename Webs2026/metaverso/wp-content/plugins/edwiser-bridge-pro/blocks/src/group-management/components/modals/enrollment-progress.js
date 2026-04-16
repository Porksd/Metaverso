import React from 'react';
import { Modal } from '@mantine/core';
import { __ } from '@wordpress/i18n';

const EnrollmentProgressModal = ({
  progressOpened,
  processed,
  total,
  percent,
}) => (
  <Modal
    opened={progressOpened}
    withCloseButton={false}
    size="sm"
    withinPortal={false}
    closeOnClickOutside={false}
  >
    <div style={{ textAlign: 'center', padding: '2.5rem' }}>
      <div
        style={{
          display: 'flex',
          alignItems: 'end',
          justifyContent: 'center',
          gap: '8px',
          marginBottom: '32px',
        }}
      >
        <span
          style={{
            fontSize: '32px',
            lineHeight: 1,
            color: '#283B3C',
            fontWeight: 700,
          }}
        >
          {processed}/{total}
        </span>
        <span
          style={{
            fontSize: '16px',
            lineHeight: 1.5,
            color: '#819596',
            fontWeight: 400,
          }}
        >
          {__('Users processed', 'edwiser-bridge-pro')}
        </span>
      </div>
      <div
        style={{
          fontSize: '40px',
          lineHeight: 1,
          color: '#00B61D',
          fontWeight: 700,
        }}
      >
        {percent}%
      </div>
    </div>
  </Modal>
);

export default EnrollmentProgressModal;
