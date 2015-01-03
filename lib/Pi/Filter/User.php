<?php
/**
 * Pi Engine (http://pialog.org)
 *
 * @link            http://code.pialog.org for the Pi Engine source repository
 * @copyright       Copyright (c) Pi Engine http://pialog.org
 * @license         http://pialog.org/license.txt BSD 3-Clause License
 */

namespace Pi\Filter;

use Pi;
use Zend\Filter\AbstractFilter;

/**
 * User name filter
 *
 * Transliterate specified format of user identifier into tag links:
 * From `@term` to
 * `<a href="<user-profile-link>/term" title="User term">@term</a>`
 *
 * @author Taiwen Jiang <taiwenjiang@tsinghua.org.cn>
 */
class User extends AbstractFilter
{
    /**
     * Filter options
     * @var array
     */
    protected $options = array(
        // Tag for user identity name
        'tag'           => '%user%',
        // Pattern for user identity
        'pattern'       => '@([a-zA-Z0-9]{3,32})',
        // Direct replacement for user identity:
        // <a href="/url/to/user/name/%user%" title="%user%">%user%</a>
        'replacement'   => '',
        // Callback for user identity replacement if no direct replacement
        'callback'      => null,
    );

    /**
     * Constructor
     *
     * @param array $options
     */
    public function __construct($options = array())
    {
        $this->setOptions($options);
        if (empty($this->options['replacement'])
            && empty($this->options['callback'])
        ) {
            $this->options['callback'] = function ($name) {
                $url = Pi::service('user')->getUrl(
                    'profile',
                    array('name' => $name)
                );
                $escapedName = _escape($name);
                return sprintf(
                    '<a href="%s" title="%s">@%s</a>',
                    $url,
                    $escapedName,
                    $escapedName
                );
            };
        }
    }

    /**
     * Transform text
     *
     * @param string $value
     * @return string
     */
    public function filter($value)
    {
        if (!empty($this->options['callback'])) {
            $value = $this->options['callback']($value);
        } else {
            $replacement = $this->options['replacement'];
            $tag = $this->options['tag'];
            $value = preg_replace_callback(
                '`' . $this->options['pattern'] . '`',
                function ($m) use ($replacement, $tag) {
                    return str_replace($tag, $m[1], $replacement);
                },
                $value
            );
        }

        return $value;
    }
}
