import React from 'react';

export function Textarea({ id, name = id, ...props }) {
    return <textarea id={id} name={name} className="ui-input ui-textarea" {...props} />;
}
