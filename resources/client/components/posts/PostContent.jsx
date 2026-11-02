import React from 'react';

function ContentBlock({ block }) {
    if (block.type === 'heading') {
        const level = Math.min(Math.max(Number(block.data?.level) || 2, 2), 4);
        const Heading = `h${level}`;

        return <Heading>{block.data?.text}</Heading>;
    }

    if (block.type === 'image' && block.data?.url) {
        return <figure><img src={block.data.url} alt={block.data.caption || ''} />{block.data.caption && <figcaption>{block.data.caption}</figcaption>}</figure>;
    }

    return <p>{block.data?.text}</p>;
}

export function PostContent({ blocks = [] }) {
    return <div className="article-body">{blocks.map((block, index) => <ContentBlock key={`${block.type}-${index}`} block={block} />)}</div>;
}
