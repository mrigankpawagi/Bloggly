((_, window) => {
  class RuntimeCss {
    constructor(id = 'default') {
      this.css = {};
      this.id = `${id}-runtime-css`;
      this.count = 0;
      this.element = document.head.querySelector(`style#${this.id}`);
      if (!this.element) {
        this.element = document.createElement('style');
        this.element.id = this.generateId();
        this.element.type = 'text/css';
        document.head.appendChild(this.element);
      }
    }

    /**
     * Adds runtime CSS to the DOM.
     *
     * @param {string|Element} selector
     *   The CSS selector to add or the Element whose #id or .class-name
     *   will be used instead.
     * @param {Object} properties
     *   A key/value list of properties that belong to the selector.
     */
    add(selector, properties = {}) {
      if (selector instanceof Element) {
        const element = selector;
        if (!element.id) {
          element.id = this.generateId();
        }
        selector = `#${element.id}`;
      }
      const current = this.css[selector] || {};
      const merged = _.extend({}, current, properties);
      if (!_.isEqual(current, merged)) {
        this.css[selector] = merged;
        this.update();
      }
    }

    /**
     * Generates a new identifier for the DOM.
     *
     * @return {string}
     *   A new identifier.
     */
    generateId() {
      this.count = this.count + 1;
      const id = `${this.id}-${this.count}`;
      if (document.querySelector(`#${id}`)) {
        return this.generateId();
      }
      return id;
    }

    /**
     * Removes runtime CSS from the DOM based on a selector.
     *
     * @param {string} selector
     *   The CSS selector to add.
     * @param {string|string[]|object} properties
     *   A list of properties to remove.
     */
    remove(selector, properties = []) {
      if (selector instanceof Element) {
        const element = selector;
        if (!element.id) {
          element.id = this.generateId();
        }
        selector = `#${element.id}`;
      }

      // Return if there's no selector present.
      if (!this.css[selector]) {
        return;
      }

      let keys = [];
      if (typeof properties === 'string') {
        keys.push(properties);
      }
      else if (properties instanceof Array) {
        keys = properties;
      }
      else if (properties instanceof Object) {
        keys = Object.keys(properties);
      }

      // Return if there are no keys.
      if (!keys.length) {
        return;
      }

      // Ommit the keys and update if necessary.
      const current = this.css[selector];
      const diff = _.omit(current, keys);
      if (!_.isEqual(current, diff)) {
        this.css[selector] = diff;
        this.update();
      }
    }

    /**
     * Updates the runtime CSS in the DOM.
     */
    update() {
      const rules = [];
      const selectors = Object.keys(this.css);
      for (let i = 0; i < selectors.length; i++) {
        const selector = selectors[i];
        const selectorProperties = Object.keys(this.css[selector]);
        let properties = [];
        for (let p = 0; p < selectorProperties.length; p++) {
          const value = this.css[selector][selectorProperties[p]];
          const prefix = RuntimeCss.prefixRegExp.test(selectorProperties[p]);
          const property = RuntimeCss.cleanProperty(selectorProperties[p]);
          if (prefix) {
            properties = properties.concat(RuntimeCss.prefix(property, value));
          }
          properties.push(`${property}:${value};`);
        }
        // If the selector can be targeted, apply the styles directly to it.
        const element = document.querySelector(selector);
        if (element) {
          element.style = properties.join(';') + ';';
        }
        else {
          rules.push(`${selector} { ${properties.join(';')}; }`);
        }
      }
      this.element.textContent = rules.join();
    }
  }

  RuntimeCss.prefixRegExp = /^-prefix-|prefix([A-Z])/;

  /**
   * Cleans a property name from camelCase to hyphen-case.
   *
   * @param {string} property
   *   The property name.
   *
   * @return {string}
   *   The cleaned property name.
   */
  RuntimeCss.cleanProperty = property => property
    .replace(RuntimeCss.prefixRegExp, (s, g1) => g1 && g1.toLowerCase() || '')
    .replace(/([a-z])([A-Z])/g, (s, g1, g2) => `${g1}-${g2}`.toLowerCase());

  /**
   * Retrieves and element's offset.
   *
   * @param {Element} [element = document.body]
   *   The element to retrieve the offset for.
   *
   * @return {{left: number, top: number}}
   *   The element offset.
   */
  RuntimeCss.getOffset = function (element = document.body) {
    return {
      left: parseInt(window.getComputedStyle(element).marginLeft, 10) + parseInt(window.getComputedStyle(element).paddingLeft, 10),
      top: parseInt(window.getComputedStyle(element).marginTop, 10) + parseInt(window.getComputedStyle(element).paddingTop, 10),
    };
  };

  /**
   * Retrieves the current scrollTop value.
   *
   * @param {Element} [element = document.body]
   *   A scrollable element.
   *
   * @return {number}
   *   The scroll top value.
   */
  RuntimeCss.getScrollTop = (element = document.body) => Math.max(window.pageYOffset, document.documentElement.scrollTop, element.scrollTop);

  /**
   * Vendor prefixes a CSS property.
   *
   * @param {string} property
   *   A property name.
   * @param {string|number} value
   *   A value.
   *
   * @return {Array}
   *   An array of prefixed properties.
   */
  RuntimeCss.prefix = (property, value) => [
    `-webkit-${property}: ${value};`,
    `-moz-${property}: ${value};`,
    `-o-${property}: ${value};`,
    `-ms-${property}: ${value};`,
    `${property}: ${value};`,
  ];

  /**
   * Flag indicating whether browser supports requestAnimationFrame.
   *
   * @type {boolean}
   */
  RuntimeCss.hasRaf = !!window.requestAnimationFrame;

  /**
   * RequestAnimationFrame wrapper.
   *
   * @param {Function} callback
   *   The callback to wrap.
   * @param {Object} [context]
   *   The "this" context, if needed. Prefer callback.bind(this) when passing
   *   the callback instead.
   *
   * @return {Number}
   *   The instance identifier.
   *
   * @see https://developer.mozilla.org/en-US/docs/Web/Events/scroll#Scroll_optimization_with_window.requestAnimationFrame
   */
  RuntimeCss.raf = (callback, context) => {
    const self = context || this;
    const fn = () => callback.call(self);
    return RuntimeCss.hasRaf ? window.requestAnimationFrame(fn) : setTimeout(fn, 0);
  };

  /**
   * Checks if a specific CSS property is supported.
   *
   * @param {string} property
   *   A CSS style property to check.
   *
   * @return {boolean}
   *   TRUE or FALSE
   */
  RuntimeCss.supports = property => document.documentElement.style[property] !== undefined ||
    document.documentElement.style[`-webkit-${property}`] !== undefined ||
    document.documentElement.style[`-moz-${property}`] !== undefined ||
    document.documentElement.style[`-o-${property}`] !== undefined ||
    document.documentElement.style[`-ms-${property}`] !== undefined;

  /**
   * @name window.RuntimeCss
   * @type {RuntimeCss}
   */
  window.RuntimeCss = window.RuntimeCss || RuntimeCss;
})(window._, window);
