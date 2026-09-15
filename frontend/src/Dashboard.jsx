import React, { useEffect, useState } from "react";
import { Button, Modal, Input, message } from "antd";
import axiosClient from "../axiosClient";


function Dashboard() {
  const [isModalOpen, setIsModalOpen] = useState(false);
  const [isFetch, setIsFetch] = useState(false);


  const [title, setTitle] = useState("");
  const [body, setBody] = useState("");

  const showModal = () => setIsModalOpen(true);

  const handleOk = async () => {
    const payload = {
      title,
      body,
    };

    try {
      const { data } = await axiosClient.post("/posts", payload);
      setIsFetch(true)

      message.success("Post created successfully!");
      setTitle("");
      setBody("");
      setIsModalOpen(false);

      console.log("Response:", data);
    } catch (error) {
      if (error.response?.status === 422) {
        message.error("Validation error! Please fill all fields.");
      } else {
        message.error("Something went wrong");
      }
      console.error(error);
    }
  };

  const handleCancel = () => {
    setIsModalOpen(false);
  };

  return (
    <>
      <div className="header-row">
        <Button type="primary" onClick={showModal}>
          Create Post
        </Button>
      </div>


      <Modal
        title="Create Post"
        open={isModalOpen}
        okText="Post"
        onOk={handleOk}
        onCancel={handleCancel}
      >
        <label>Title</label>
        <Input
          placeholder="Enter post title"
          value={title}
          onChange={(e) => setTitle(e.target.value)}
        />

        <label style={{ marginTop: "15px", display: "block" }}>Body</label>
        <Input.TextArea
          rows={4}
          placeholder="Enter post body"
          value={body}
          onChange={(e) => setBody(e.target.value)}
        />
      </Modal>
    </>
  );
}

export default Dashboard;
