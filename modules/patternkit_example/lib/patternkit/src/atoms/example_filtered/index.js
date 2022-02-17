(function () {
  'use strict';

  import './src/example_filtered.scss';
  import example_filtered from './dist/example_filtered';

  var element = Object.create(HTMLElement.prototype);
  element.createdCallback = function () {};
  element.attachedCallback = function () {};
  element.detachedCallback = function () {};
  element.attributeChangedCallback = function (attr, oldVal, newVal) {};
  document.registerElement('example_filtered', {
    prototype: element
  });
}());
