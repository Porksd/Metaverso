import { TextInput } from '@mantine/core';
import React from 'react';

function GroupsName({
  groups,
  groupsData,
  updateGroupsData,
  validationErrors,
}) {
  return (
    <div className="eb-checkout__groups-name">
      <div className="groups-name__header">
        <h2 className="groups-name__title">Groups name</h2>
      </div>
      <div className="groups-name__content">
        {groups.map((group) => (
          <TextInput
            key={group.id}
            type={group.type}
            id={group.id}
            label={group.label}
            placeholder={group.placeholder}
            className={group.class}
            value={groupsData[group.id] || ''}
            onChange={(e) => updateGroupsData(group.id, e.currentTarget.value)}
            error={validationErrors?.[group.id]}
            required
          />
        ))}
      </div>
    </div>
  );
}

export default GroupsName;
