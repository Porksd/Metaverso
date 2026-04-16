import { Select, Skeleton } from '@mantine/core';
import { __ } from '@wordpress/i18n';
import React from 'react';
import { Icons } from './icons';
import { useDisclosure } from '@mantine/hooks';
import AddProduct from './modals/add-product';
import AddQuantity from './modals/add-quantity';
import DeleteGroup from './modals/delete-group';

function GroupActions({
  groups,
  selectedGroup,
  setSelectedGroup,
  fetchAddProductData,
  addProductData,
  isAddProductLoading,
  fetchAddQuantityData,
  addQuantityData,
  isAddQuantityLoading,
  addProductsToCart,
  deleteGroup,
  isDeletingGroup,
}) {
  const [productOpened, { open: openProduct, close: closeProduct }] =
    useDisclosure(false);
  const [quantityOpened, { open: openQuantity, close: closeQuantity }] =
    useDisclosure(false);
  const [deleteOpened, { open: openDelete, close: closeDelete }] =
    useDisclosure(false);

  return (
    <div className="eb-group-management__actions">
      <Select
        className="actions__select-group"
        label={__('Group', 'edwiser-bridge-pro')}
        placeholder={__('Select group', 'edwiser-bridge-pro')}
        data={groups.map((group) => {
          return {
            value: group.mdl_cohort_id,
            label: group.display_text,
          };
        })}
        comboboxProps={{
          withinPortal: false,
        }}
        checkIconPosition="right"
        maxDropdownHeight={350}
        rightSection={<Icons.chevronDown />}
        allowDeselect={false}
        value={selectedGroup}
        onChange={(group) => setSelectedGroup(group)}
      />
      <div className="actions__btn">
        <button
          className="btn__add-product"
          disabled={!selectedGroup}
          onClick={openProduct}
        >
          {__('Add product', 'edwiser-bridge-pro')}
        </button>
        <button
          className="btn__add-quantity"
          disabled={!selectedGroup}
          onClick={openQuantity}
        >
          {__('Add seats', 'edwiser-bridge-pro')}
        </button>
        <button
          className="btn__delete-group"
          disabled={!selectedGroup}
          onClick={openDelete}
        >
          {__('Delete group', 'edwiser-bridge-pro')}
        </button>
      </div>

      {/* Add Product Modal */}
      <AddProduct
        productOpened={productOpened}
        closeProduct={closeProduct}
        groupId={selectedGroup}
        fetchAddProductData={fetchAddProductData}
        addProductData={addProductData}
        isAddProductLoading={isAddProductLoading}
        addProductsToCart={addProductsToCart}
      />

      {/* Add Quantity Modal */}
      <AddQuantity
        quantityOpened={quantityOpened}
        closeQuantity={closeQuantity}
        groupId={selectedGroup}
        fetchAddQuantityData={fetchAddQuantityData}
        addQuantityData={addQuantityData}
        isAddQuantityLoading={isAddQuantityLoading}
        addProductsToCart={addProductsToCart}
      />

      {/* Delete Group Modal */}
      <DeleteGroup
        deleteOpened={deleteOpened}
        closeDelete={closeDelete}
        cohortId={selectedGroup}
        onDelete={deleteGroup}
        isDeletingGroup={isDeletingGroup}
      />
    </div>
  );
}

export default GroupActions;

export function GroupActionsSkeleton() {
  return (
    <div className="eb-group-management__actions">
      <Skeleton w={300} h={34} />
      <div className="actions__btn">
        <Skeleton w={100} h={34} />
        <Skeleton w={100} h={34} />
        <Skeleton w={100} h={34} />
      </div>
    </div>
  );
}
