import React from 'react';

export function Select({ id, name = id, children, ...props }) {
    return <select id={id} name={name} className="ui-input ui-select" {...props}>{children}</select>;
}
