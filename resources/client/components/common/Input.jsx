import React from 'react';

export function Input({ id, name = id, ...props }) {
    return <input id={id} name={name} className="ui-input" {...props} />;
}
